<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM learners WHERE id = ?')->execute([$id]);
        flash_set('success', 'Learner account deleted.');
    }
    redirect(base_url('/admin/users.php'));
}

$stmt = db()->query(
    'SELECT l.*, COUNT(qa.id) AS attempt_count
     FROM learners l
     LEFT JOIN quiz_attempts qa ON qa.learner_id = l.id
     GROUP BY l.id
     ORDER BY l.created_at DESC'
);
$learners = $stmt->fetchAll();

$adminPageTitle = 'Registered Users';
$activeAdminNav = 'users';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Registered Users</h1></div>

<?php if (!$learners): ?>
  <p class="admin-empty">No learners have registered yet.</p>
<?php else: ?>
<div class="table-scroll">
<table class="admin-table">
  <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Quiz Attempts</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($learners as $l): ?>
    <tr>
      <td class="wrap"><?php echo e($l['name']); ?></td>
      <td class="wrap"><?php echo e($l['email']); ?></td>
      <td><?php echo e(format_date($l['created_at'])); ?></td>
      <td><?php echo (int) $l['attempt_count']; ?></td>
      <td>
        <div class="admin-table__actions">
          <a href="<?php echo e(base_url('/admin/user-view.php?id=' . $l['id'])); ?>" class="btn btn--outline btn--sm">View</a>
          <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int) $l['id']; ?>">
            <button type="submit" class="icon-btn-sm" title="Delete" data-confirm="Delete this learner account and all their quiz history? This cannot be undone."><?php echo icon('trash'); ?></button>
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
