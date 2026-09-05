<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$id = (int) ($_GET['id'] ?? 0);
$quiz = null;
$existingQuestions = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM quizzes WHERE id = ?');
    $stmt->execute([$id]);
    $quiz = $stmt->fetch();
    if (!$quiz) {
        flash_set('error', 'Quiz not found.');
        redirect(base_url('/admin/quizzes.php'));
    }

    $qStmt = db()->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY display_order, id');
    $qStmt->execute([$id]);
    foreach ($qStmt->fetchAll() as $row) {
        $opts = [];
        if ($row['question_type'] === 'mcq') {
            $optStmt = db()->prepare('SELECT * FROM quiz_options WHERE question_id = ? ORDER BY display_order, id');
            $optStmt->execute([$row['id']]);
            $opts = $optStmt->fetchAll();
        }
        $existingQuestions[] = ['question' => $row, 'options' => $opts];
    }
}

/** Renders one existing question in the exact markup shape quiz-builder.js expects. */
function render_existing_question_block(int $index, array $q, array $options): void
{
    $type = $q['question_type'];
    $typeLabel = $type === 'mcq' ? 'Multiple Choice' : ($type === 'fill_blank' ? 'Fill in the Blank' : 'One Word Answer');
    $prefix = "questions[{$index}]";
    ?>
    <div class="quiz-question-block" data-question-block data-index="<?php echo $index; ?>">
      <div class="quiz-question-block__head">
        <span class="quiz-question-block__type"><?php echo e($typeLabel); ?></span>
        <div class="quiz-question-block__order">
          <button type="button" class="icon-btn-sm" title="Move up" data-move-up><?php echo icon('arrow-up'); ?></button>
          <button type="button" class="icon-btn-sm" title="Move down" data-move-down><?php echo icon('arrow-down'); ?></button>
          <button type="button" class="icon-btn-sm" title="Delete question" data-remove-question><?php echo icon('trash'); ?></button>
        </div>
      </div>
      <input type="hidden" name="<?php echo $prefix; ?>[type]" value="<?php echo e($type); ?>">
      <input type="hidden" name="<?php echo $prefix; ?>[order]" value="<?php echo $index + 1; ?>" data-order-input>
      <div class="form-field">
        <label>Question text</label>
        <textarea name="<?php echo $prefix; ?>[text]" rows="2" required><?php echo e($q['question_text']); ?></textarea>
        <?php if ($type === 'fill_blank'): ?><p class="form-hint">Use ___ where the blank goes.</p><?php endif; ?>
      </div>
      <?php if ($type === 'mcq'): ?>
      <div class="form-field">
        <label>Options (select the correct one)</label>
        <?php for ($i = 0; $i < 4; $i++): $opt = $options[$i] ?? ['option_text' => '', 'is_correct' => 0]; ?>
        <div class="quiz-option-row">
          <input type="radio" name="<?php echo $prefix; ?>[correct]" value="<?php echo $i; ?>" <?php echo (int) $opt['is_correct'] === 1 ? 'checked' : ''; ?>>
          <input type="text" name="<?php echo $prefix; ?>[options][<?php echo $i; ?>]" required value="<?php echo e($opt['option_text']); ?>" placeholder="Option <?php echo $i + 1; ?>">
        </div>
        <?php endfor; ?>
      </div>
      <?php else: ?>
      <div class="form-field">
        <label>Accepted answer(s)</label>
        <div data-answers-list>
          <?php $answers = $q['accepted_answers'] !== null && $q['accepted_answers'] !== '' ? explode('|', $q['accepted_answers']) : ['']; ?>
          <?php foreach ($answers as $ans): ?>
          <div class="quiz-accepted-answer-row">
            <input type="text" name="<?php echo $prefix; ?>[answers][]" required value="<?php echo e($ans); ?>" placeholder="Accepted answer">
            <button type="button" class="icon-btn-sm" title="Remove this answer" data-remove-answer><?php echo icon('close'); ?></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn--outline btn--sm" data-add-answer>+ Add another accepted answer</button>
        <p class="form-hint">Add spelling variants separately, e.g. "colour" and "color".</p>
      </div>
      <?php endif; ?>
      <div class="form-field">
        <label>Explanation (optional)</label>
        <textarea name="<?php echo $prefix; ?>[explanation]" rows="2" placeholder="Shown to the learner after they answer"><?php echo e($q['explanation'] ?? ''); ?></textarea>
      </div>
    </div>
    <?php
}

$errors = [];
$old = [
    'title'       => $quiz['title'] ?? '',
    'topic'       => $quiz['topic'] ?? '',
    'description' => $quiz['description'] ?? '',
    'status'      => $quiz['status'] ?? 'draft',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['title'] = trim((string) ($_POST['title'] ?? ''));
        $old['topic'] = trim((string) ($_POST['topic'] ?? ''));
        $old['description'] = trim((string) ($_POST['description'] ?? ''));
        $old['status'] = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        if ($old['title'] === '' || mb_strlen($old['title']) > 255) {
            $errors[] = 'Please enter a quiz title.';
        }

        $questionsInput = $_POST['questions'] ?? [];
        $parsedQuestions = [];

        foreach ($questionsInput as $data) {
            $type = $data['type'] ?? '';
            if (!in_array($type, ['mcq', 'fill_blank', 'one_word'], true)) {
                continue;
            }

            $text = trim((string) ($data['text'] ?? ''));
            $explanation = trim((string) ($data['explanation'] ?? ''));
            $order = (int) ($data['order'] ?? 0);
            if ($text === '') {
                continue;
            }
            $shortLabel = mb_strimwidth($text, 0, 50, '...');

            if ($type === 'mcq') {
                $options = array_map('trim', (array) ($data['options'] ?? []));
                $options = array_slice(array_pad($options, 4, ''), 0, 4);
                $correctIndex = (int) ($data['correct'] ?? 0);
                if (in_array('', $options, true)) {
                    $errors[] = 'Please fill in all four options for: "' . $shortLabel . '"';
                    continue;
                }
                $parsedQuestions[] = ['type' => $type, 'text' => $text, 'explanation' => $explanation, 'order' => $order, 'options' => $options, 'correct' => $correctIndex];
            } else {
                $answers = array_values(array_filter(array_map(static fn ($a) => str_replace('|', '', trim((string) $a)), (array) ($data['answers'] ?? [])), static fn ($a) => $a !== ''));
                if (!$answers) {
                    $errors[] = 'Please enter at least one accepted answer for: "' . $shortLabel . '"';
                    continue;
                }
                $parsedQuestions[] = ['type' => $type, 'text' => $text, 'explanation' => $explanation, 'order' => $order, 'answers' => $answers];
            }
        }

        usort($parsedQuestions, static fn ($a, $b) => $a['order'] <=> $b['order']);

        if (!$parsedQuestions && !$errors) {
            $errors[] = 'Please add at least one question.';
        }

        if (!$errors) {
            $db = db();
            $db->beginTransaction();
            try {
                if ($quiz) {
                    $slug = $quiz['slug'];
                    if (($_POST['regenerate_slug'] ?? '') === '1') {
                        $slug = unique_slug($old['title'], 'quizzes', (int) $quiz['id']);
                    }
                    $stmt = $db->prepare('UPDATE quizzes SET title=?, slug=?, topic=?, description=?, status=? WHERE id=?');
                    $stmt->execute([$old['title'], $slug, $old['topic'] ?: null, $old['description'] ?: null, $old['status'], $quiz['id']]);
                    $quizId = (int) $quiz['id'];
                    $db->prepare('DELETE FROM quiz_questions WHERE quiz_id = ?')->execute([$quizId]);
                } else {
                    $slug = unique_slug($old['title'], 'quizzes');
                    $stmt = $db->prepare('INSERT INTO quizzes (title, slug, topic, description, status) VALUES (?,?,?,?,?)');
                    $stmt->execute([$old['title'], $slug, $old['topic'] ?: null, $old['description'] ?: null, $old['status']]);
                    $quizId = (int) $db->lastInsertId();
                }

                $order = 1;
                foreach ($parsedQuestions as $pq) {
                    if ($pq['type'] === 'mcq') {
                        $stmt = $db->prepare('INSERT INTO quiz_questions (quiz_id, question_type, question_text, explanation, display_order) VALUES (?,?,?,?,?)');
                        $stmt->execute([$quizId, 'mcq', $pq['text'], $pq['explanation'] ?: null, $order]);
                        $qid = (int) $db->lastInsertId();
                        foreach ($pq['options'] as $i => $optText) {
                            $optStmt = $db->prepare('INSERT INTO quiz_options (question_id, option_text, is_correct, display_order) VALUES (?,?,?,?)');
                            $optStmt->execute([$qid, $optText, $i === $pq['correct'] ? 1 : 0, $i]);
                        }
                    } else {
                        $stmt = $db->prepare('INSERT INTO quiz_questions (quiz_id, question_type, question_text, accepted_answers, explanation, display_order) VALUES (?,?,?,?,?,?)');
                        $stmt->execute([$quizId, $pq['type'], $pq['text'], implode('|', $pq['answers']), $pq['explanation'] ?: null, $order]);
                    }
                    $order++;
                }

                $db->commit();
                flash_set('success', $quiz ? 'Quiz updated.' : 'Quiz created.');
                redirect(base_url('/admin/quizzes.php'));
            } catch (Throwable $e) {
                $db->rollBack();
                app_log('Quiz save failed: ' . $e->getMessage());
                $errors[] = 'Something went wrong saving the quiz. Please try again.';
            }
        }
    }
}

$adminPageTitle = $quiz ? 'Edit Quiz' : 'Add Quiz';
$activeAdminNav = 'quizzes';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1><?php echo e($adminPageTitle); ?></h1></div>

<?php if ($errors): ?>
<div class="flash flash--error">
  <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" id="quiz-form">
  <?php echo csrf_field(); ?>
  <div class="admin-card" style="margin-bottom:16px;">
    <div class="form-field">
      <label for="title">Quiz title</label>
      <input type="text" id="title" name="title" required maxlength="255" value="<?php echo e($old['title']); ?>">
    </div>
    <div class="form-row">
      <div class="form-field">
        <label for="topic">Topic / category</label>
        <input type="text" id="topic" name="topic" maxlength="150" value="<?php echo e($old['topic']); ?>" placeholder="e.g. Grammar">
      </div>
      <div class="form-field">
        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="draft" <?php echo $old['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
          <option value="published" <?php echo $old['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
        </select>
      </div>
    </div>
    <div class="form-field">
      <label for="description">Short description (optional)</label>
      <textarea id="description" name="description" rows="2" maxlength="500"><?php echo e($old['description']); ?></textarea>
    </div>
    <?php if ($quiz): ?>
    <div class="form-field">
      <label style="font-weight:600;"><input type="checkbox" name="regenerate_slug" value="1" style="width:auto;"> Update internal address to match the new title</label>
    </div>
    <?php endif; ?>
  </div>

  <h2>Questions</h2>
  <div id="questions-container" data-next-index="<?php echo count($existingQuestions); ?>">
    <?php if (!$existingQuestions): ?>
      <p class="admin-empty" id="no-questions-note">No questions yet. Click "Add Question" below to start.</p>
    <?php else: ?>
      <?php foreach ($existingQuestions as $i => $eq): render_existing_question_block($i, $eq['question'], $eq['options']); endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="add-question-menu" style="margin:16px 0 24px;">
    <button type="button" class="btn btn--primary" id="add-question-btn"><?php echo icon('plus'); ?> Add Question</button>
    <div class="add-question-menu__list" id="add-question-menu-list">
      <button type="button" data-add-type="mcq">Multiple Choice</button>
      <button type="button" data-add-type="fill_blank">Fill in the Blank</button>
      <button type="button" data-add-type="one_word">One Word Answer</button>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Save Quiz</button>
    <a href="<?php echo e(base_url('/admin/quizzes.php')); ?>" class="btn btn--outline">Cancel</a>
  </div>
</form>

<?php $extraScripts = ['/admin/assets/quiz-builder.js']; ?>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
