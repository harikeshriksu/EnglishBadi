<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';
require_once __DIR__ . '/includes/editor-widget.php';

$id = (int) ($_GET['id'] ?? 0);
$lesson = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM lessons WHERE id = ?');
    $stmt->execute([$id]);
    $lesson = $stmt->fetch();
    if (!$lesson) {
        flash_set('error', 'Lesson not found.');
        redirect(base_url('/admin/lessons.php'));
    }
}

$categories = get_categories('lesson');
$errors = [];

$old = [
    'title'            => $lesson['title'] ?? '',
    'category_id'      => $lesson['category_id'] ?? '',
    'excerpt'          => $lesson['excerpt'] ?? '',
    'body'             => $lesson['body'] ?? '',
    'status'           => $lesson['status'] ?? 'draft',
    'meta_description' => $lesson['meta_description'] ?? '',
    'publish_date'     => ($lesson['publish_date'] ?? null) ? date('Y-m-d\TH:i', strtotime($lesson['publish_date'])) : date('Y-m-d\TH:i'),
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['title'] = trim((string) ($_POST['title'] ?? ''));
        $old['category_id'] = (string) ($_POST['category_id'] ?? '');
        $old['excerpt'] = trim((string) ($_POST['excerpt'] ?? ''));
        $old['body'] = (string) ($_POST['body'] ?? '');
        $old['status'] = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $old['meta_description'] = trim((string) ($_POST['meta_description'] ?? ''));
        $old['publish_date'] = (string) ($_POST['publish_date'] ?? '');

        if ($old['title'] === '' || mb_strlen($old['title']) > 255) {
            $errors[] = 'Please enter a title (up to 255 characters).';
        }

        $sanitizedBody = sanitize_html($old['body']);
        if (trim(strip_tags($sanitizedBody)) === '' && !str_contains($sanitizedBody, '<img')) {
            $errors[] = 'Please write the lesson body.';
        }

        $ts = $old['publish_date'] !== '' ? strtotime($old['publish_date']) : false;
        $publishDateSql = $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');

        $categoryIdValue = $old['category_id'] !== '' ? (int) $old['category_id'] : null;
        $excerptValue = $old['excerpt'] !== '' ? $old['excerpt'] : excerpt_from_html($sanitizedBody, 200);

        $imagePaths = [
            'featured_image'      => $lesson['featured_image'] ?? null,
            'featured_image_webp' => $lesson['featured_image_webp'] ?? null,
            'featured_thumb'      => $lesson['featured_thumb'] ?? null,
            'featured_thumb_webp' => $lesson['featured_thumb_webp'] ?? null,
        ];

        if (!$errors && !empty($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $result = process_lesson_image($_FILES['featured_image']['tmp_name'], $_FILES['featured_image']['name']);
                delete_image_files(array_values($imagePaths));
                $imagePaths = [
                    'featured_image'      => $result['image_path'],
                    'featured_image_webp' => $result['webp_path'],
                    'featured_thumb'      => $result['thumb_path'],
                    'featured_thumb_webp' => $result['webp_thumb_path'],
                ];
            } catch (ImageProcessingException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$errors) {
            if ($lesson) {
                $slug = $lesson['slug'];
                if (($_POST['regenerate_slug'] ?? '') === '1') {
                    $slug = unique_slug($old['title'], 'lessons', (int) $lesson['id']);
                }
                $stmt = db()->prepare(
                    'UPDATE lessons SET title=?, slug=?, category_id=?, featured_image=?, featured_image_webp=?, featured_thumb=?, featured_thumb_webp=?, excerpt=?, body=?, status=?, meta_description=?, publish_date=? WHERE id=?'
                );
                $stmt->execute([
                    $old['title'], $slug, $categoryIdValue,
                    $imagePaths['featured_image'], $imagePaths['featured_image_webp'], $imagePaths['featured_thumb'], $imagePaths['featured_thumb_webp'],
                    $excerptValue, $sanitizedBody, $old['status'], $old['meta_description'] ?: null, $publishDateSql,
                    $lesson['id'],
                ]);
                flash_set('success', 'Lesson updated.');
            } else {
                $slug = unique_slug($old['title'], 'lessons');
                $stmt = db()->prepare(
                    'INSERT INTO lessons (title, slug, category_id, featured_image, featured_image_webp, featured_thumb, featured_thumb_webp, excerpt, body, status, meta_description, publish_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $old['title'], $slug, $categoryIdValue,
                    $imagePaths['featured_image'], $imagePaths['featured_image_webp'], $imagePaths['featured_thumb'], $imagePaths['featured_thumb_webp'],
                    $excerptValue, $sanitizedBody, $old['status'], $old['meta_description'] ?: null, $publishDateSql,
                ]);
                flash_set('success', 'Lesson created.');
            }
            redirect(base_url('/admin/lessons.php'));
        }

        $old['body'] = $sanitizedBody;
    }
}

$adminPageTitle = $lesson ? 'Edit Lesson' : 'Add Lesson';
$activeAdminNav = 'lessons';
$loadPublicCss = true;
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
    <label for="title">Title</label>
    <input type="text" id="title" name="title" required maxlength="255" value="<?php echo e($old['title']); ?>">
  </div>

  <?php if ($lesson): ?>
  <div class="form-field">
    <label style="font-weight:600;"><input type="checkbox" name="regenerate_slug" value="1" style="width:auto;"> Change the web address to match the new title</label>
    <p class="form-hint">Current address: /lessons/<?php echo e($lesson['slug']); ?> - leave unchecked to keep old links working.</p>
  </div>
  <?php endif; ?>

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
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="draft" <?php echo $old['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
        <option value="published" <?php echo $old['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
      </select>
    </div>
    <div class="form-field">
      <label for="publish_date">Publish date</label>
      <input type="datetime-local" id="publish_date" name="publish_date" value="<?php echo e($old['publish_date']); ?>">
    </div>
  </div>

  <div class="form-field">
    <label for="featured_image">Featured image <?php echo ($lesson && $lesson['featured_image']) ? '(uploading a new one replaces the current image)' : '(optional)'; ?></label>
    <?php if ($lesson && $lesson['featured_image']): ?>
      <p><img src="<?php echo e(upload_url($lesson['featured_thumb'] ?: $lesson['featured_image'])); ?>" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:8px;"></p>
    <?php endif; ?>
    <input type="file" id="featured_image" name="featured_image" accept="image/*">
    <p class="form-hint">Recommended size: about 800 &times; 800 pixels.</p>
  </div>

  <div class="form-field">
    <label for="excerpt">Excerpt (optional)</label>
    <textarea id="excerpt" name="excerpt" rows="2" maxlength="300"><?php echo e($old['excerpt']); ?></textarea>
    <p class="form-hint">Shown in the lesson list. Leave blank to auto-generate from the body.</p>
  </div>

  <div class="form-field">
    <label>Body</label>
    <?php render_editor('body', $old['body']); ?>
  </div>

  <div class="form-field">
    <label for="meta_description">Search description (optional)</label>
    <textarea id="meta_description" name="meta_description" rows="2" maxlength="300"><?php echo e($old['meta_description']); ?></textarea>
    <p class="form-hint">Shown in Google search results. Leave blank to auto-generate.</p>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Save Lesson</button>
    <a href="<?php echo e(base_url('/admin/lessons.php')); ?>" class="btn btn--outline">Cancel</a>
  </div>
</form>

<?php $extraScripts = ['/admin/assets/editor.js']; ?>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
