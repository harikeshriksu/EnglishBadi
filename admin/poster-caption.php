<?php
/**
 * Mandatory finishing step right after a batch poster upload: every
 * newly-uploaded poster must be captioned here before the upload is
 * considered done - there is no "skip for now" option. (Existing posters
 * can still have their caption/details changed later from poster-edit.php.)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($_GET['ids'] ?? $_POST['ids'] ?? ''))))));

if (!$ids) {
    flash_set('error', 'No newly-uploaded posters to caption.');
    redirect(base_url('/admin/posters.php'));
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT * FROM posters WHERE id IN ({$placeholders}) ORDER BY FIELD(id, {$placeholders})");
$stmt->execute(array_merge($ids, $ids));
$posters = $stmt->fetchAll();

if (!$posters) {
    flash_set('error', 'Those posters could not be found.');
    redirect(base_url('/admin/posters.php'));
}

$categories = get_categories('poster');
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $captions = $_POST['caption'] ?? [];
        foreach ($posters as $p) {
            if (trim((string) ($captions[$p['id']] ?? '')) === '') {
                $errors[] = 'Please enter a caption for every poster before finishing.';
                break;
            }
        }

        if (!$errors) {
            $db = db();
            $db->beginTransaction();
            try {
                foreach ($posters as $p) {
                    $caption = trim((string) ($captions[$p['id']] ?? ''));
                    $altText = trim((string) ($_POST['alt_text'][$p['id']] ?? ''));
                    $categoryId = (string) ($_POST['category_id'][$p['id']] ?? '');
                    $categoryIdValue = $categoryId !== '' ? (int) $categoryId : null;

                    $stmt = $db->prepare('UPDATE posters SET caption = ?, alt_text = ?, category_id = ? WHERE id = ?');
                    $stmt->execute([$caption, $altText ?: null, $categoryIdValue, $p['id']]);
                }
                $db->commit();
                flash_set('success', count($posters) === 1 ? 'Poster captioned.' : count($posters) . ' posters captioned.');
                redirect(base_url('/admin/posters.php'));
            } catch (Throwable $e) {
                $db->rollBack();
                app_log('Poster captioning failed: ' . $e->getMessage());
                $errors[] = 'Something went wrong saving these captions. Please try again.';
            }
        }
    }
}

$adminPageTitle = 'Caption Your New Posters';
$activeAdminNav = 'posters';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Caption Your New Posters</h1></div>
<p style="color:var(--color-ink-light);margin-bottom:20px;">
  <?php echo count($posters) === 1 ? 'Your poster has been uploaded.' : 'Your ' . count($posters) . ' posters have been uploaded.'; ?>
  A caption is required for each one before you're done.
</p>

<?php if ($errors): ?>
<div class="flash flash--error">
  <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?php echo e(base_url('/admin/poster-caption.php')); ?>">
  <?php echo csrf_field(); ?>
  <input type="hidden" name="ids" value="<?php echo e(implode(',', $ids)); ?>">

  <?php foreach ($posters as $p): ?>
  <div class="admin-card" style="display:flex;gap:16px;align-items:flex-start;margin-bottom:16px;">
    <img src="<?php echo e(upload_url($p['thumb_path'])); ?>" alt="" style="width:100px;height:100px;object-fit:cover;border-radius:8px;flex-shrink:0;">
    <div style="flex:1;">
      <div class="form-field">
        <label for="caption-<?php echo (int) $p['id']; ?>">Caption (required)</label>
        <input type="text" id="caption-<?php echo (int) $p['id']; ?>" name="caption[<?php echo (int) $p['id']; ?>]" required maxlength="300" value="<?php echo e($p['caption'] ?? ''); ?>">
      </div>
      <div class="form-row">
        <div class="form-field">
          <label for="alt-<?php echo (int) $p['id']; ?>">Alt text (optional, for accessibility &amp; SEO)</label>
          <input type="text" id="alt-<?php echo (int) $p['id']; ?>" name="alt_text[<?php echo (int) $p['id']; ?>]" maxlength="255" value="<?php echo e($p['alt_text'] ?? ''); ?>">
        </div>
        <div class="form-field">
          <label for="cat-<?php echo (int) $p['id']; ?>">Category</label>
          <select id="cat-<?php echo (int) $p['id']; ?>" name="category_id[<?php echo (int) $p['id']; ?>]">
            <option value="">No category</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?php echo (int) $c['id']; ?>" <?php echo (string) ($p['category_id'] ?? '') === (string) $c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Finish</button>
  </div>
</form>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
