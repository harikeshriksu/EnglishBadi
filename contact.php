<?php
require_once __DIR__ . '/includes/config.php';

$stmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$stmt->execute(['contact']);
$page = $stmt->fetch();

$errors = [];
$old = ['name' => '', 'email' => '', 'message' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please reload the page and try again.';
    } else {
        $old['name'] = trim((string) ($_POST['name'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['message'] = trim((string) ($_POST['message'] ?? ''));
        $honeypot = trim((string) ($_POST['website'] ?? ''));
        $formLoadedAt = (int) ($_POST['form_loaded_at'] ?? 0);
        $looksLikeBot = $honeypot !== '' || (time() - $formLoadedAt) < 3;

        if ($old['name'] === '' || mb_strlen($old['name']) > 150) {
            $errors[] = 'Please enter your name.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($old['message'] === '') {
            $errors[] = 'Please enter a message.';
        } elseif (mb_strlen($old['message']) > 5000) {
            $errors[] = 'Your message is too long (5000 characters maximum).';
        }

        if (!$errors) {
            if (!$looksLikeBot) {
                $ins = db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)');
                $ins->execute([$old['name'], $old['email'], $old['message']]);

                $recipient = setting('contact_email');
                if ($recipient) {
                    $body = "New message from the English Badi contact form:\n\n"
                        . 'Name: ' . $old['name'] . "\n"
                        . 'Email: ' . $old['email'] . "\n\n"
                        . "Message:\n" . $old['message'] . "\n";
                    send_mail_message($recipient, 'New contact message - ' . setting('site_title', 'English Badi'), $body, $old['email']);
                }
            }
            // Same success message whether or not this looked like spam, so a
            // bot (or a sender we quietly filtered) can't tell it was blocked.
            flash_set('success', "Thanks! Your message has been sent. We'll get back to you soon.");
            redirect(base_url('/contact'));
        }
    }
}

$pageSeo = [
    'title'       => $page['title'] ?? 'Contact Us',
    'description' => $page['meta_description'] ?? 'Get in touch with English Badi.',
];
$activeNav = 'contact';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" style="max-width:600px;">
  <h1 class="page-title"><?php echo e($page['title'] ?? 'Contact Us'); ?></h1>
  <?php if ($page && trim($page['body']) !== ''): ?>
  <div class="content-body"><?php echo $page['body']; ?></div>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
