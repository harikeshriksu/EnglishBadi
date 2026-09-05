<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('SELECT featured_image, featured_image_webp, featured_thumb, featured_thumb_webp FROM lessons WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            delete_image_files(array_values($row));
            db()->prepare('DELETE FROM lessons WHERE id = ?')->execute([$id]);
            flash_set('success', 'Lesson deleted.');
        }
    }
    redirect(base_url('/admin/lessons.php'));
}

$search = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);

$where = [];
$params = [];
if ($search !== '') {
    $where[] = 'l.title LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($categoryId) {
    $where[] = 'l.category_id = ?';
    $params[] = $categoryId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare("SELECT l.*, c.name AS category_name FROM lessons l LEFT JOIN categories c ON c.id = l.category_id {$whereSql} ORDER BY l.updated_at DESC");
$stmt->execute($params);
$lessons = $stmt->fetchAll();

$categories = get_categories('lesson');

$adminPageTitle = 'Lessons';
$activeAdminNav = 'lessons';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header">
  <h1>Lessons</h1>
  <a href="<?php echo e(base_url('/admin/lesson-form.php')); ?>" class="btn btn--primary"><?php echo icon('plus'); ?> Add a lesson</a>
</div>

<form method="get" class="admin-filter-bar">
  <input type="text" name="q" placeholder="Search by title..." value="<?php echo e($search); ?>">
  <select name="category" data-autosubmit>
    <option value="0">All categories</option>
    <?php foreach ($categories as $c): ?>
    <option value="<?php echo (int) $c['id']; ?>" <?php echo $categoryId === (int) $c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn--outline btn--sm">Filter</button>
</form>

<?php if (!$lessons): ?>
  <p class="admin-empty">No lessons found. <a href="<?php echo e(base_url('/admin/lesson-form.php')); ?>">Add your first lesson</a>.</p>
<?php else: ?>
<div class="table-scroll">
<table class="admin-table">
  <thead><tr><th></th><th>Title</th><th>Category</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($lessons as $l): $thumb = upload_url($l['featured_thumb']); ?>
    <tr>
      <td><?php if ($thumb): ?><img src="<?php echo e($thumb); ?>" class="admin-table__thumb" alt=""><?php endif; ?></td>
      <td class="wrap"><?php echo e($l['title']); ?></td>
      <td><?php echo e($l['category_name'] ?? '-'); ?></td>
      <td><?php echo e(format_date($l['publish_date'])); ?></td>
      <td><span class="badge badge--<?php echo e($l['status']); ?>"><?php echo e($l['status']); ?></span></td>
      <td>
        <div class="admin-table__actions">
          <a href="<?php echo e(base_url('/admin/lesson-form.php?id=' . $l['id'])); ?>" class="icon-btn-sm" title="Edit"><?php echo icon('edit'); ?></a>
          <form method="post" action="<?php echo e(base_url('/admin/lessons.php')); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int) $l['id']; ?>">
            <button type="submit" class="icon-btn-sm" title="Delete" data-confirm="Delete this lesson? This cannot be undone."><?php echo icon('trash'); ?></button>
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
