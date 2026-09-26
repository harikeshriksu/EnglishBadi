<?php
require_once __DIR__ . '/includes/config.php';

$stmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$stmt->execute(['about']);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$contactStmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$contactStmt->execute(['contact']);
$contactPage = $contactStmt->fetch();

$errors = [];
$old = ['name' => '', 'email' => '', 'message' => ''];

$pageSeo = [
    'title'       => $page['title'],
    'description' => $page['meta_description'] ?: excerpt_from_html($page['body'], 160),
];
$activeNav = 'about';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" style="max-width:760px;">
  <h1 class="page-title"><?php echo e($page['title']); ?></h1>
  <div class="content-body">
    <?php echo $page['body']; ?>
  </div>

  <div id="contact" style="margin-top:48px;">
    <h2 class="page-title" style="font-size:1.5rem;"><?php echo e($contactPage['title'] ?? 'Contact Us'); ?></h2>
    <?php if ($contactPage && trim($contactPage['body']) !== ''): ?>
    <div class="content-body"><?php echo $contactPage['body']; ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
    <div class="flash flash--error">
      <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(base_url('/contact')); ?>" class="form-card" style="margin-top:20px;max-width:none;">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="form_loaded_at" value="<?php echo time(); ?>">
      <div class="form-honeypot" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="form-field">
        <label for="name">Your name</label>
        <input type="text" id="name" name="name" required maxlength="150" value="<?php echo e($old['name']); ?>">
      </div>
      <div class="form-field">
        <label for="email">Your email</label>
        <input type="email" id="email" name="email" required maxlength="190" value="<?php echo e($old['email']); ?>">
      </div>
      <div class="form-field">
        <label for="message">Message</label>
        <textarea id="message" name="message" required rows="6" maxlength="5000"><?php echo e($old['message']); ?></textarea>
      </div>
      <button type="submit" class="btn btn--primary btn--block">Send message</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
