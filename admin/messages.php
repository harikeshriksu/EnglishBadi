<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($action === 'mark_read') {
            db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
            flash_set('success', 'Message deleted.');
        }
    }
    redirect(base_url('/admin/messages.php'));
}

$messages = db()->query('SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC')->fetchAll();

$adminPageTitle = 'Messages';
$activeAdminNav = 'messages';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Messages</h1></div>

<?php if (!$messages): ?>
  <p class="admin-empty">No messages yet.</p>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px;">
<?php foreach ($messages as $m): ?>
  <div class="admin-card" style="<?php echo $m['is_read'] ? '' : 'border-left:4px solid var(--color-blue);'; ?>">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <div>
        <strong><?php echo e($m['name']); ?></strong> &lt;<?php echo e($m['email']); ?>&gt;
        <?php if (!$m['is_read']): ?><span class="badge badge--draft">New</span><?php endif; ?>
      </div>
      <span style="color:var(--color-ink-light);font-size:.85rem;"><?php echo e(format_date($m['created_at'], 'd M Y, g:i A')); ?></span>
    </div>
    <p style="white-space:pre-wrap;margin:12px 0;"><?php echo e($m['message']); ?></p>
    <div class="form-actions" style="margin-top:0;">
      <?php if (!$m['is_read']): ?>
      <form method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>"><button type="submit" class="btn btn--outline btn--sm">Mark as read</button></form>
      <?php endif; ?>
      <a href="mailto:<?php echo e($m['email']); ?>" class="btn btn--outline btn--sm">Reply by email</a>
      <form method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>"><button type="submit" class="btn btn--danger btn--sm" data-confirm="Delete this message?">Delete</button></form>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
