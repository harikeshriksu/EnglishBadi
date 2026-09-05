<?php
require_once __DIR__ . '/includes/config.php';

if (is_learner_logged_in()) {
    redirect(base_url('/my-progress'));
}

$redirectTo = (string) ($_GET['redirect'] ?? $_POST['redirect'] ?? '/my-progress');
if (!str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
    $redirectTo = '/my-progress';
}

$errors = [];
$old = ['email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $result = learner_login($old['email'], $password);
        if ($result['ok']) {
            redirect(base_url($redirectTo));
        }
        $errors[] = $result['error'];
    }
}

$pageSeo = [
    'title'       => 'Login',
    'description' => 'Log in to your English Badi account.',
];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="form-card">
    <h1 class="page-title text-center">Login</h1>

    <?php if ($errors): ?>
    <div class="flash flash--error">
      <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(base_url('/login')); ?>">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="redirect" value="<?php echo e($redirectTo); ?>">
      <div class="form-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?php echo e($old['email']); ?>">
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn--primary btn--block">Log in</button>
    </form>
    <p class="text-center" style="margin-top:12px;"><a href="<?php echo e(base_url('/forgot-password')); ?>">Forgot your password?</a></p>
    <p class="text-center"><a href="<?php echo e(base_url('/register')); ?>">Don't have an account? Register</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
