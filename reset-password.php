<?php
require_once __DIR__ . '/includes/config.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$reset = $token !== '' ? verify_password_reset_token($token) : null;

if (!$reset) {
    $pageSeo = ['title' => 'Reset Password', 'description' => 'Reset your English Badi account password.', 'noindex' => true];
    $activeNav = '';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container section">
      <div class="form-card">
        <h1 class="page-title text-center">Link expired</h1>
        <p>This password reset link is invalid or has expired. Please request a new one.</p>
        <p class="text-center"><a href="<?php echo e(base_url('/forgot-password')); ?>" class="btn btn--primary">Request new link</a></p>
      </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$errors = [];
$done = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        if (mb_strlen($password) < 6) {
            $errors[] = 'Your password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            consume_password_reset_token((int) $reset['id'], (int) $reset['learner_id'], $password);
            $done = true;
        }
    }
}

$pageSeo = ['title' => 'Reset Password', 'description' => 'Reset your English Badi account password.', 'noindex' => true];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="form-card">
    <h1 class="page-title text-center">Reset Password</h1>
    <?php if ($done): ?>
      <p>Your password has been updated.</p>
      <p class="text-center"><a href="<?php echo e(base_url('/login')); ?>" class="btn btn--primary">Log in</a></p>
    <?php else: ?>
      <?php if ($errors): ?>
      <div class="flash flash--error">
        <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>
      <form method="post" action="<?php echo e(base_url('/reset-password')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="token" value="<?php echo e($token); ?>">
        <div class="form-field">
          <label for="password">New password</label>
          <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="form-field">
          <label for="password_confirm">Confirm new password</label>
          <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
        </div>
        <button type="submit" class="btn btn--primary btn--block">Set new password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
