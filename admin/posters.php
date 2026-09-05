<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        flash_set('error', 'Your session expired. Please try again.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = db()->prepare('SELECT image_path, thumb_path, webp_path, webp_thumb_path FROM posters WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            delete_image_files(array_values($row));
            db()->prepare('DELETE FROM posters WHERE id = ?')->execute([$id]);
            flash_set('success', 'Poster deleted.');
        }
    }
    redirect(base_url('/admin/posters.php'));
}

$posters = db()->query('SELECT p.*, c.name AS category_name FROM posters p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.display_order, p.created_at DESC')->fetchAll();

$adminPageTitle = 'Posters';
$activeAdminNav = 'posters';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Posters</h1></div>

<div class="poster-dropzone" id="poster-dropzone" tabindex="0" role="button" aria-label="Add posters: click or drop image files here">
  <?php echo icon('upload-cloud'); ?>
  <p style="font-weight:700;margin:0;">Click here, or drag and drop images to add posters</p>
  <p class="poster-dropzone__note">Best size: 1080 &times; 1080 pixels (square). Accepts JPG, PNG, WEBP, GIF, BMP, TIFF, HEIC or PDF. You can add many at once and caption them afterwards.</p>
</div>

<form id="poster-upload-form" action="<?php echo e(base_url('/admin/poster-upload.php')); ?>" method="post" enctype="multipart/form-data">
  <?php echo csrf_field(); ?>
  <input type="file" id="poster-file-input" accept="image/*,.pdf,.heic,.heif" multiple class="visually-hidden">
  <div id="poster-queue" class="poster-queue"></div>
  <button type="submit" id="poster-upload-btn" class="btn btn--primary" disabled>Upload</button>
</form>

<h2 style="margin-top:30px;">All posters</h2>
<?php if (!$posters): ?>
  <p class="admin-empty">No posters yet. Add some above.</p>
<?php else: ?>
<div class="admin-poster-grid">
  <?php foreach ($posters as $p): ?>
  <div class="admin-poster-grid__item">
    <img src="<?php echo e(upload_url($p['thumb_path'])); ?>" alt="<?php echo e($p['caption'] ?: ''); ?>" loading="lazy">
    <div class="admin-poster-grid__overlay">
      <a href="<?php echo e(base_url('/admin/poster-edit.php?id=' . $p['id'])); ?>" title="Edit"><?php echo icon('edit'); ?></a>
      <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
        <button type="submit" title="Delete" data-confirm="Delete this poster? This cannot be undone."><?php echo icon('trash'); ?></button>
      </form>
    </div>
    <?php if ($p['caption']): ?><span class="admin-poster-grid__caption"><?php echo e($p['caption']); ?></span><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php $extraScripts = ['/admin/assets/crop.js', '/admin/assets/poster-manager.js']; ?>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
