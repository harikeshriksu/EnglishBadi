<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('SELECT thumbnail, thumbnail_webp FROM links WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            delete_image_files(array_values($row));
            db()->prepare('DELETE FROM links WHERE id = ?')->execute([$id]);
            flash_set('success', 'Link deleted.');
        }
    }
    redirect(base_url('/admin/links.php'));
}

$search = trim((string) ($_GET['q'] ?? ''));
$where = [];
$params = [];
if ($search !== '') {
    $where[] = 'l.name LIKE ?';
    $params[] = '%' . $search . '%';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare("SELECT l.*, c.name AS category_name FROM links l LEFT JOIN categories c ON c.id = l.category_id {$whereSql} ORDER BY l.display_order, l.created_at DESC");
$stmt->execute($params);
$links = $stmt->fetchAll();

$adminPageTitle = 'Links';
$activeAdminNav = 'links';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header">
  <h1>Links</h1>
  <a href="<?php echo e(base_url('/admin/link-form.php')); ?>" class="btn btn--primary"><?php echo icon('plus'); ?> Add a link</a>
</div>

<form method="get" class="admin-filter-bar">
  <input type="text" name="q" placeholder="Search by name..." value="<?php echo e($search); ?>">
  <button type="submit" class="btn btn--outline btn--sm">Search</button>
</form>

<?php if (!$links): ?>
  <p class="admin-empty">No links found. <a href="<?php echo e(base_url('/admin/link-form.php')); ?>">Add your first link</a>.</p>
<?php else: ?>
<div class="table-scroll">
<table class="admin-table">
  <thead><tr><th>Name</th><th>URL</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($links as $l): ?>
    <tr>
      <td class="wrap"><?php echo e($l['name']); ?><?php if ($l['youtube_video_id']): ?> <span class="badge badge--published">YouTube</span><?php endif; ?></td>
      <td class="wrap"><a href="<?php echo e($l['url']); ?>" target="_blank" rel="noopener"><?php echo e(mb_strimwidth($l['url'], 0, 50, '...')); ?></a></td>
      <td><?php echo e($l['category_name'] ?? '-'); ?></td>
      <td><span class="badge badge--<?php echo $l['status'] === 'published' ? 'published' : 'draft'; ?>"><?php echo e($l['status']); ?></span></td>
      <td>
        <div class="admin-table__actions">
          <a href="<?php echo e(base_url('/admin/link-form.php?id=' . $l['id'])); ?>" class="icon-btn-sm" title="Edit"><?php echo icon('edit'); ?></a>
          <form method="post" action="<?php echo e(base_url('/admin/links.php')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int) $l['id']; ?>">
            <button type="submit" class="icon-btn-sm" title="Delete" data-confirm="Delete this link? This cannot be undone."><?php echo icon('trash'); ?></button>
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
