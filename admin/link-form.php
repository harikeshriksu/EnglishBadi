<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$id = (int) ($_GET['id'] ?? 0);
$link = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM links WHERE id = ?');
    $stmt->execute([$id]);
    $link = $stmt->fetch();
    if (!$link) {
        flash_set('error', 'Link not found.');
        redirect(base_url('/admin/links.php'));
    }
}

$categories = get_categories('link');
$errors = [];
$old = [
    'name'          => $link['name'] ?? '',
    'description'   => $link['description'] ?? '',
    'url'           => $link['url'] ?? '',
    'category_id'   => $link['category_id'] ?? '',
    'display_order' => $link['display_order'] ?? 0,
    'status'        => $link['status'] ?? 'published',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['name'] = trim((string) ($_POST['name'] ?? ''));
        $old['description'] = trim((string) ($_POST['description'] ?? ''));
        $old['url'] = trim((string) ($_POST['url'] ?? ''));
        $old['category_id'] = (string) ($_POST['category_id'] ?? '');
        $old['display_order'] = (int) ($_POST['display_order'] ?? 0);
        $old['status'] = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';

        if ($old['name'] === '' || mb_strlen($old['name']) > 255) {
            $errors[] = 'Please enter a link name.';
        }
        if (!filter_var($old['url'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $old['url'])) {
            $errors[] = 'Please enter a valid URL starting with http:// or https://.';
        }

        $youtubeId = youtube_extract_id($old['url']);
        $categoryIdValue = $old['category_id'] !== '' ? (int) $old['category_id'] : null;

        $thumbPaths = ['thumbnail' => $link['thumbnail'] ?? null, 'thumbnail_webp' => $link['thumbnail_webp'] ?? null];
        if (!$errors && !$youtubeId && !empty($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            try {
                $result = process_link_thumbnail($_FILES['thumbnail']['tmp_name'], $_FILES['thumbnail']['name']);
                delete_image_files(array_values($thumbPaths));
                $thumbPaths = ['thumbnail' => $result['image_path'], 'thumbnail_webp' => $result['webp_path']];
            } catch (ImageProcessingException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$errors) {
            if ($link) {
                $slug = $link['slug'];
                if (($_POST['regenerate_slug'] ?? '') === '1') {
                    $slug = unique_slug($old['name'], 'links', (int) $link['id']);
                }
                $stmt = db()->prepare(
                    'UPDATE links SET name=?, slug=?, description=?, url=?, category_id=?, thumbnail=?, thumbnail_webp=?, youtube_video_id=?, display_order=?, status=? WHERE id=?'
                );
                $stmt->execute([
                    $old['name'], $slug, $old['description'] ?: null, $old['url'], $categoryIdValue,
                    $thumbPaths['thumbnail'], $thumbPaths['thumbnail_webp'], $youtubeId, $old['display_order'], $old['status'],
                    $link['id'],
                ]);
                flash_set('success', 'Link updated.');
            } else {
                $slug = unique_slug($old['name'], 'links');
                $stmt = db()->prepare(
                    'INSERT INTO links (name, slug, description, url, category_id, thumbnail, thumbnail_webp, youtube_video_id, display_order, status) VALUES (?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $old['name'], $slug, $old['description'] ?: null, $old['url'], $categoryIdValue,
                    $thumbPaths['thumbnail'], $thumbPaths['thumbnail_webp'], $youtubeId, $old['display_order'], $old['status'],
                ]);
                flash_set('success', 'Link created.');
            }
            redirect(base_url('/admin/links.php'));
        }
    }
}

$adminPageTitle = $link ? 'Edit Link' : 'Add Link';
$activeAdminNav = 'links';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1><?php echo e($adminPageTitle); ?></h1></div>

<?php if ($errors): ?>
<div class="flash flash--error">
  <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-card">
  <?php echo csrf_field(); ?>
  <div class="form-field">
    <label for="name">Link name</label>
    <input type="text" id="name" name="name" required maxlength="255" value="<?php echo e($old['name']); ?>">
  </div>
  <div class="form-field">
    <label for="url">URL</label>
    <input type="url" id="url" name="url" required maxlength="500" value="<?php echo e($old['url']); ?>" placeholder="https://..." data-youtube-check>
    <p class="form-hint" id="youtube-preview-hint"></p>
    <div id="youtube-preview"></div>
  </div>
  <div class="form-field">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="3" maxlength="500"><?php echo e($old['description']); ?></textarea>
    <p class="form-hint">1-3 lines explaining what the learner will get from it.</p>
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
      <p class="form-hint">Lower numbers show first.</p>
    </div>
    <div class="form-field">
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="published" <?php echo $old['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
        <option value="draft" <?php echo $old['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
      </select>
    </div>
  </div>

  <?php if ($link && $link['youtube_video_id']): ?>
    <p class="form-hint">This link uses its YouTube thumbnail automatically - no need to upload one.</p>
  <?php else: ?>
  <div class="form-field">
    <label for="thumbnail">Thumbnail image (optional)</label>
    <?php if ($link && $link['thumbnail']): ?><p><img src="<?php echo e(upload_url($link['thumbnail'])); ?>" alt="" style="width:100px;height:100px;object-fit:cover;border-radius:8px;"></p><?php endif; ?>
    <input type="file" id="thumbnail" name="thumbnail" accept="image/*">
    <p class="form-hint">If left blank, a simple letter icon will be shown instead. Not needed for YouTube links.</p>
  </div>
  <?php endif; ?>

  <?php if ($link): ?>
  <div class="form-field">
    <label style="font-weight:600;"><input type="checkbox" name="regenerate_slug" value="1" style="width:auto;"> Update internal address to match the new name</label>
  </div>
  <?php endif; ?>

  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Save Link</button>
    <a href="<?php echo e(base_url('/admin/links.php')); ?>" class="btn btn--outline">Cancel</a>
  </div>
</form>

<?php $extraScripts = ['/admin/assets/link-form.js']; ?>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
