<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';
require_once __DIR__ . '/includes/editor-widget.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM pages WHERE id = ?');
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    flash_set('error', 'Page not found.');
    redirect(base_url('/admin/pages.php'));
}

$errors = [];
$old = [
    'title'            => $page['title'],
    'body'             => $page['body'],
    'meta_description' => $page['meta_description'] ?? '',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['title'] = trim((string) ($_POST['title'] ?? ''));
        $old['body'] = (string) ($_POST['body'] ?? '');
        $old['meta_description'] = trim((string) ($_POST['meta_description'] ?? ''));

        if ($old['title'] === '') {
            $errors[] = 'Please enter a page title.';
        }
        $sanitizedBody = sanitize_html($old['body']);
        if (trim(strip_tags($sanitizedBody)) === '') {
            $errors[] = 'Please write the page content.';
        }

        if (!$errors) {
            $stmt = db()->prepare('UPDATE pages SET title = ?, body = ?, meta_description = ? WHERE id = ?');
            $stmt->execute([$old['title'], $sanitizedBody, $old['meta_description'] ?: null, $page['id']]);
            flash_set('success', 'Page updated.');
            redirect(base_url('/admin/pages.php'));
        }

        $old['body'] = $sanitizedBody;
    }
}

$adminPageTitle = 'Edit: ' . $page['title'];
$activeAdminNav = 'pages';
$loadPublicCss = true;
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Edit Page: <?php echo e($page['title']); ?></h1></div>
<p style="color:var(--color-ink-light);margin-bottom:16px;">Address: /<?php echo e($page['slug']); ?></p>

<?php if ($errors): ?>
<div class="flash flash--error">
  <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" class="admin-card">
  <?php echo csrf_field(); ?>
  <div class="form-field">
    <label for="title">Page title</label>
    <input type="text" id="title" name="title" required maxlength="255" value="<?php echo e($old['title']); ?>">
  </div>
  <div class="form-field">
    <label>Content</label>
    <?php render_editor('body', $old['body']); ?>
  </div>
  <div class="form-field">
    <label for="meta_description">Search description (optional)</label>
    <textarea id="meta_description" name="meta_description" rows="2" maxlength="300"><?php echo e($old['meta_description']); ?></textarea>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn--primary">Save Page</button>
    <a href="<?php echo e(base_url('/admin/pages.php')); ?>" class="btn btn--outline">Cancel</a>
  </div>
</form>

<?php $extraScripts = ['/admin/assets/editor.js']; ?>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
