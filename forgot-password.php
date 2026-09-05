<?php
require_once __DIR__ . '/includes/config.php';

$sent = false;
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $stmt = db()->prepare('SELECT id, name FROM learners WHERE email = ?');
            $stmt->execute([$email]);
            $learner = $stmt->fetch();

            if ($learner) {
                $token = create_password_reset_token((int) $learner['id']);
                $resetUrl = base_url('/reset-password?token=' . urlencode($token));
                $body = 'Hello ' . $learner['name'] . ",\n\n"
                    . "We received a request to reset your English Badi password. Click the link below to choose a new one. This link expires in 1 hour.\n\n"
                    . $resetUrl . "\n\n"
                    . "If you didn't request this, you can safely ignore this email.\n";
                send_mail_message($email, 'Reset your English Badi password', $body);
            }
            // Same message whether or not the email is registered, so this
            // form can't be used to discover which addresses have accounts.
            $sent = true;
        }
    }
}

$pageSeo = [
    'title'       => 'Forgot Password',
    'description' => 'Reset your English Badi account password.',
];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="form-card">
    <h1 class="page-title text-center">Forgot Password</h1>

    <?php if ($sent): ?>
      <p>If an account exists for that email address, a password reset link has been sent. Please check your inbox.</p>
      <p class="text-center"><a href="<?php echo e(base_url('/login')); ?>">Back to login</a></p>
    <?php else: ?>
      <?php if ($errors): ?>
      <div class="flash flash--error">
        <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <p class="page-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
      <form method="post" action="<?php echo e(base_url('/forgot-password')); ?>">
        <?php echo csrf_field(); ?>
        <div class="form-field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block">Send reset link</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
