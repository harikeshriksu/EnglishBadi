<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM posters WHERE id = ?');
$stmt->execute([$id]);
$poster = $stmt->fetch();

if (!$poster) {
    flash_set('error', 'Poster not found.');
    redirect(base_url('/admin/posters.php'));
}

$categories = get_categories('poster');
$errors = [];
$old = [
    'caption'       => $poster['caption'] ?? '',
    'alt_text'      => $poster['alt_text'] ?? '',
    'category_id'   => $poster['category_id'] ?? '',
    'display_order' => $poster['display_order'] ?? 0,
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['caption'] = trim((string) ($_POST['caption'] ?? ''));
        $old['alt_text'] = trim((string) ($_POST['alt_text'] ?? ''));
        $old['category_id'] = (string) ($_POST['category_id'] ?? '');
        $old['display_order'] = (int) ($_POST['display_order'] ?? 0);

        $categoryIdValue = $old['category_id'] !== '' ? (int) $old['category_id'] : null;
        $stmt = db()->prepare('UPDATE posters SET caption = ?, alt_text = ?, category_id = ?, display_order = ? WHERE id = ?');
        $stmt->execute([$old['caption'] ?: null, $old['alt_text'] ?: null, $categoryIdValue, $old['display_order'], $poster['id']]);
        flash_set('success', 'Poster updated.');
        redirect(base_url('/admin/posters.php'));
    }
}

$adminPageTitle = 'Edit Poster';
$activeAdminNav = 'posters';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Edit Poster</h1></div>

<?php if ($errors): ?>
<div class="flash flash--error">
  <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="admin-card">
  <p><img src="<?php echo e(upload_url($poster['image_path'])); ?>" alt="" style="max-width:280px;border-radius:10px;"></p>

  <form method="post" style="margin-top:16px;">
    <?php echo csrf_field(); ?>
    <div class="form-field">
      <label for="caption">Caption (optional)</label>
      <input type="text" id="caption" name="caption" maxlength="300" value="<?php echo e($old['caption']); ?>">
    </div>
    <div class="form-field">
      <label for="alt_text">Alt text (optional, for accessibility &amp; SEO)</label>
      <input type="text" id="alt_text" name="alt_text" maxlength="255" value="<?php echo e($old['alt_text']); ?>">
    </div>
    <div class="form-row">
      <div class="form-field">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">No category</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?php echo (int) $c['id']; ?>" <?php echo (string) $old['category_id'] === (string) $c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-field">
        <label for="display_order">Display order</label>
        <input type="number" id="display_order" name="display_order" value="<?php echo (int) $old['display_order']; ?>">
        <p class="form-hint">Lower numbers show first in the gallery.</p>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save</button>
      <a href="<?php echo e(base_url('/admin/posters.php')); ?>" class="btn btn--outline">Cancel</a>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
