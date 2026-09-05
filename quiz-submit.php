<?php
/**
 * JSON endpoint: grades a submitted quiz attempt authoritatively against
 * the database (never trusting a score computed in the browser) and
 * stores it if the visitor is a logged-in learner.
 */
require_once __DIR__ . '/includes/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request method.'], 405);
}

$payload = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($payload) || !isset($payload['quiz_id'], $payload['answers']) || !is_array($payload['answers'])) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
}

if (!csrf_verify($payload['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'error' => 'Your session expired. Please reload the quiz and try again.'], 400);
}

$quizId = (int) $payload['quiz_id'];

$stmt = db()->prepare("SELECT id FROM quizzes WHERE id = ? AND status = 'published'");
$stmt->execute([$quizId]);
if (!$stmt->fetch()) {
    json_response(['ok' => false, 'error' => 'Quiz not found.'], 404);
}

$answers = [];
foreach ($payload['answers'] as $questionId => $value) {
    if (!is_scalar($value)) {
        continue;
    }
    $answers[(int) $questionId] = mb_substr((string) $value, 0, 500);
}

$learner = current_learner();
$previous = $learner ? get_previous_attempt(db(), (int) $learner['id'], $quizId) : null;

$graded = grade_quiz_attempt(db(), $quizId, $answers);

$saved = false;
if ($learner) {
    save_quiz_attempt(db(), $quizId, (int) $learner['id'], $graded);
    $saved = true;
}

json_response([
    'ok'         => true,
    'score'      => $graded['score'],
    'total'      => $graded['total'],
    'percentage' => $graded['percentage'],
    'results'    => $graded['results'],
    'saved'      => $saved,
    'previous'   => $previous ? ['score' => (int) $previous['score'], 'total' => (int) $previous['total']] : null,
]);
