<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM learners WHERE id = ?');
$stmt->execute([$id]);
$learner = $stmt->fetch();

if (!$learner) {
    flash_set('error', 'Learner not found.');
    redirect(base_url('/admin/users.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(base_url('/admin/user-view.php?id=' . $id));
    }
    db()->prepare('DELETE FROM learners WHERE id = ?')->execute([$id]);
    flash_set('success', 'Learner account deleted.');
    redirect(base_url('/admin/users.php'));
}

$history = get_learner_quiz_history(db(), $id);

$adminPageTitle = 'Learner: ' . $learner['name'];
$activeAdminNav = 'users';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1><?php echo e($learner['name']); ?></h1></div>
<p style="color:var(--color-ink-light);">Email: <?php echo e($learner['email']); ?> &middot; Joined <?php echo e(format_date($learner['created_at'])); ?></p>

<?php if (!$history): ?>
  <p class="admin-empty">This learner hasn't taken any quizzes yet.</p>
<?php else: ?>
<div class="table-scroll">
<table class="admin-table">
  <thead><tr><th>Quiz</th><th>Score</th><th>Percentage</th><th>Date</th></tr></thead>
  <tbody>
  <?php foreach ($history as $a): ?>
    <tr>
      <td class="wrap"><?php echo e($a['quiz_title']); ?></td>
      <td><?php echo (int) $a['score']; ?>/<?php echo (int) $a['total']; ?></td>
      <td><?php echo e(number_format((float) $a['percentage'], 0)); ?>%</td>
      <td><?php echo e(format_date($a['created_at'], 'd M Y, g:i A')); ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

<form method="post" style="margin-top:24px;">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="action" value="delete">
  <button type="submit" class="btn btn--danger" data-confirm="Delete this learner account and all their quiz history? This cannot be undone.">Delete this learner's account</button>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
