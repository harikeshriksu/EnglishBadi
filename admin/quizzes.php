<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$id]);
        flash_set('success', 'Quiz deleted.');
    }
    redirect(base_url('/admin/quizzes.php'));
}

$stmt = db()->query(
    "SELECT q.id, q.title, q.topic, q.status,
            (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) AS question_count,
            (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) AS attempt_count
     FROM quizzes q
     ORDER BY q.updated_at DESC"
);
$quizzes = $stmt->fetchAll();

$adminPageTitle = 'Quizzes';
$activeAdminNav = 'quizzes';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header">
  <h1>Quizzes</h1>
  <a href="<?php echo e(base_url('/admin/quiz-form.php')); ?>" class="btn btn--primary"><?php echo icon('plus'); ?> Add a quiz</a>
</div>

<?php if (!$quizzes): ?>
  <p class="admin-empty">No quizzes found. <a href="<?php echo e(base_url('/admin/quiz-form.php')); ?>">Add your first quiz</a>.</p>
<?php else: ?>
<div class="table-scroll">
<table class="admin-table">
  <thead><tr><th>Title</th><th>Topic</th><th>Questions</th><th>Attempts</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($quizzes as $q): ?>
    <tr>
      <td class="wrap"><?php echo e($q['title']); ?></td>
      <td><?php echo e($q['topic']); ?></td>
      <td><?php echo (int) $q['question_count']; ?></td>
      <td><?php echo (int) $q['attempt_count']; ?></td>
      <td><span class="badge badge--<?php echo $q['status'] === 'published' ? 'published' : 'draft'; ?>"><?php echo e($q['status']); ?></span></td>
      <td>
        <div class="admin-table__actions">
          <a href="<?php echo e(base_url('/admin/quiz-form.php?id=' . $q['id'])); ?>" class="icon-btn-sm" title="Edit"><?php echo icon('edit'); ?></a>
          <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int) $q['id']; ?>">
            <button type="submit" class="icon-btn-sm" title="Delete" data-confirm="Delete this quiz and all its questions and attempt history? This cannot be undone."><?php echo icon('trash'); ?></button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
