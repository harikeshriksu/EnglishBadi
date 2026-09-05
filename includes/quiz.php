<?php
/**
 * Quiz grading logic shared between the public quiz-taking page
 * (quiz-submit.php) and anywhere else scores need to be recomputed.
 * The browser grades each question instantly for a responsive feel, but
 * the score that gets stored always comes from grade_quiz_attempt() here
 * re-checking the learner's answers against the database - the client's
 * own tally is never trusted for storage.
 */

function normalize_answer(string $text): string
{
    $text = trim($text);
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return mb_strtolower($text, 'UTF-8');
}

function answer_matches_accepted(string $given, string $acceptedPipeList): bool
{
    $given = normalize_answer($given);
    if ($given === '') {
        return false;
    }
    foreach (explode('|', $acceptedPipeList) as $accepted) {
        if ($accepted !== '' && normalize_answer($accepted) === $given) {
            return true;
        }
    }
    return false;
}

function get_quiz_by_slug(string $slug): ?array
{
    $stmt = db()->prepare("SELECT * FROM quizzes WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function get_quiz_question_count(int $quizId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?');
    $stmt->execute([$quizId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Data for rendering the quiz-taking page, including correct answers, so
 * the browser can grade each question and show feedback instantly without
 * a network round-trip per question.
 */
function get_quiz_questions_for_taking(PDO $db, int $quizId): array
{
    $stmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY display_order, id');
    $stmt->execute([$quizId]);
    $questions = $stmt->fetchAll();

    foreach ($questions as &$q) {
        if ($q['question_type'] === 'mcq') {
            $optStmt = $db->prepare('SELECT id, option_text, is_correct FROM quiz_options WHERE question_id = ? ORDER BY display_order, id');
            $optStmt->execute([$q['id']]);
            $q['options'] = $optStmt->fetchAll();
        } else {
            $q['options'] = [];
        }
    }
    unset($q);

    return $questions;
}

/**
 * Authoritatively grade a submitted attempt. $answers is
 * [question_id => submitted value], where the value is an option id
 * (mcq) or free text (fill_blank / one_word). Client-submitted scores
 * are never used - everything is re-derived from the database here.
 */
function grade_quiz_attempt(PDO $db, int $quizId, array $answers): array
{
    $stmt = $db->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY display_order, id');
    $stmt->execute([$quizId]);
    $questions = $stmt->fetchAll();

    $results = [];
    $score = 0;

    foreach ($questions as $q) {
        $given = $answers[$q['id']] ?? '';
        $isCorrect = false;
        $correctDisplay = '';
        $givenDisplay = '';

        if ($q['question_type'] === 'mcq') {
            $optStmt = $db->prepare('SELECT id, option_text, is_correct FROM quiz_options WHERE question_id = ? ORDER BY display_order, id');
            $optStmt->execute([$q['id']]);
            $options = $optStmt->fetchAll();

            $givenOptionId = (int) $given;

            foreach ($options as $opt) {
                if ((int) $opt['is_correct'] === 1) {
                    $correctDisplay = $opt['option_text'];
                }
                if ((int) $opt['id'] === $givenOptionId) {
                    $givenDisplay = $opt['option_text'];
                    if ((int) $opt['is_correct'] === 1) {
                        $isCorrect = true;
                    }
                }
            }
        } else {
            $isCorrect = answer_matches_accepted((string) $given, (string) $q['accepted_answers']);
            $firstAccepted = explode('|', (string) $q['accepted_answers']);
            $correctDisplay = trim($firstAccepted[0] ?? '');
            $givenDisplay = (string) $given;
        }

        if ($isCorrect) {
            $score++;
        }

        $results[] = [
            'question_id'    => (int) $q['id'],
            'question_text'  => $q['question_text'],
            'question_type'  => $q['question_type'],
            'given_answer'   => $givenDisplay,
            'correct_answer' => $correctDisplay,
            'is_correct'     => $isCorrect,
            'explanation'    => $q['explanation'],
        ];
    }

    $total = count($questions);

    return [
        'score'      => $score,
        'total'      => $total,
        'percentage' => $total > 0 ? round(($score / $total) * 100, 2) : 0.0,
        'results'    => $results,
    ];
}

function save_quiz_attempt(PDO $db, int $quizId, int $learnerId, array $graded): int
{
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO quiz_attempts (quiz_id, learner_id, score, total, percentage) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$quizId, $learnerId, $graded['score'], $graded['total'], $graded['percentage']]);
        $attemptId = (int) $db->lastInsertId();

        $ansStmt = $db->prepare('INSERT INTO quiz_attempt_answers (attempt_id, question_id, learner_answer, is_correct) VALUES (?, ?, ?, ?)');
        foreach ($graded['results'] as $r) {
            $ansStmt->execute([$attemptId, $r['question_id'], $r['given_answer'], $r['is_correct'] ? 1 : 0]);
        }

        $db->commit();
        return $attemptId;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * The learner's most recent attempt at this quiz BEFORE the one currently
 * being submitted. Call this before save_quiz_attempt() for the current
 * attempt, or the "last time" comparison will just show itself.
 */
function get_previous_attempt(PDO $db, int $learnerId, int $quizId): ?array
{
    $stmt = $db->prepare(
        'SELECT * FROM quiz_attempts WHERE learner_id = ? AND quiz_id = ? ORDER BY created_at DESC, id DESC LIMIT 1'
    );
    $stmt->execute([$learnerId, $quizId]);
    return $stmt->fetch() ?: null;
}

function get_best_attempt(PDO $db, int $learnerId, int $quizId): ?array
{
    $stmt = $db->prepare(
        'SELECT * FROM quiz_attempts WHERE learner_id = ? AND quiz_id = ? ORDER BY score DESC, created_at ASC LIMIT 1'
    );
    $stmt->execute([$learnerId, $quizId]);
    return $stmt->fetch() ?: null;
}

function get_learner_quiz_history(PDO $db, int $learnerId): array
{
    $stmt = $db->prepare(
        'SELECT qa.*, q.title AS quiz_title, q.slug AS quiz_slug
         FROM quiz_attempts qa
         JOIN quizzes q ON q.id = qa.quiz_id
         WHERE qa.learner_id = ?
         ORDER BY qa.created_at ASC, qa.id ASC'
    );
    $stmt->execute([$learnerId]);
    return $stmt->fetchAll();
}
