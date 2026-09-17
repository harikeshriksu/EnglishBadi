<?php
require_once __DIR__ . '/includes/config.php';

if (is_learner_logged_in()) {
    redirect(base_url('/my-progress'));
}

$redirectTo = (string) ($_GET['redirect'] ?? $_POST['redirect'] ?? '/my-progress');
if (!str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
    $redirectTo = '/my-progress';
}

$activeTab = ($_GET['tab'] ?? '') === 'register' ? 'register' : 'login';

$loginErrors = [];
$loginOld = ['email' => ''];
$registerErrors = [];
$registerOld = ['name' => '', 'email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $formType = ($_POST['form'] ?? '') === 'register' ? 'register' : 'login';
    $activeTab = $formType;

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        if ($formType === 'register') {
            $registerErrors[] = 'Your session expired. Please try again.';
        } else {
            $loginErrors[] = 'Your session expired. Please try again.';
        }
    } elseif ($formType === 'register') {
        $registerOld['name'] = trim((string) ($_POST['name'] ?? ''));
        $registerOld['email'] = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $result = learner_register($registerOld['name'], $registerOld['email'], $password);
        if ($result['ok']) {
            flash_set('success', 'Welcome! Your account has been created.');
            redirect(base_url('/my-progress'));
        }
        $registerErrors[] = $result['error'];
    } else {
        $loginOld['email'] = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $result = learner_login($loginOld['email'], $password);
        if ($result['ok']) {
            redirect(base_url($redirectTo));
        }
        $loginErrors[] = $result['error'];
    }
}

$pageSeo = [
    'title'       => 'Login / Register',
    'description' => 'Log in or create a free English Badi account to track your quiz scores over time.',
];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="form-card">
    <div class="auth-tabs" role="tablist">
      <button type="button" role="tab" id="auth-tab-login" aria-controls="auth-panel-login" aria-selected="<?php echo $activeTab === 'login' ? 'true' : 'false'; ?>" class="auth-tabs__btn <?php echo $activeTab === 'login' ? 'is-active' : ''; ?>" data-auth-tab="login">I already have an account</button>
      <button type="button" role="tab" id="auth-tab-register" aria-controls="auth-panel-register" aria-selected="<?php echo $activeTab === 'register' ? 'true' : 'false'; ?>" class="auth-tabs__btn <?php echo $activeTab === 'register' ? 'is-active' : ''; ?>" data-auth-tab="register">I'm new here</button>
    </div>

    <div id="auth-panel-login" role="tabpanel" aria-labelledby="auth-tab-login" class="auth-panel" <?php echo $activeTab === 'login' ? '' : 'hidden'; ?>>
      <h1 class="page-title text-center">Login</h1>

      <?php if ($loginErrors): ?>
      <div class="flash flash--error">
        <?php foreach ($loginErrors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="post" action="<?php echo e(base_url('/login')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form" value="login">
        <input type="hidden" name="redirect" value="<?php echo e($redirectTo); ?>">
        <div class="form-field">
          <label for="login-email">Email</label>
          <input type="email" id="login-email" name="email" required value="<?php echo e($loginOld['email']); ?>">
        </div>
        <div class="form-field">
          <label for="login-password">Password</label>
          <input type="password" id="login-password" name="password" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block">Log in</button>
      </form>
      <p class="text-center" style="margin-top:12px;"><a href="<?php echo e(base_url('/forgot-password')); ?>">Forgot your password?</a></p>
    </div>

    <div id="auth-panel-register" role="tabpanel" aria-labelledby="auth-tab-register" class="auth-panel" <?php echo $activeTab === 'register' ? '' : 'hidden'; ?>>
      <h1 class="page-title text-center">Register</h1>
      <p class="page-subtitle text-center">Optional, and free. Track your quiz scores over time.</p>

      <?php if ($registerErrors): ?>
      <div class="flash flash--error">
        <?php foreach ($registerErrors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <form method="post" action="<?php echo e(base_url('/login')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form" value="register">
        <div class="form-field">
          <label for="register-name">Name</label>
          <input type="text" id="register-name" name="name" required maxlength="150" value="<?php echo e($registerOld['name']); ?>">
        </div>
        <div class="form-field">
          <label for="register-email">Email</label>
          <input type="email" id="register-email" name="email" required maxlength="190" value="<?php echo e($registerOld['email']); ?>">
        </div>
        <div class="form-field">
          <label for="register-password">Password</label>
          <input type="password" id="register-password" name="password" required minlength="6">
          <p class="form-hint">At least 6 characters.</p>
        </div>
        <button type="submit" class="btn btn--primary btn--block">Create account</button>
      </form>
    </div>
  </div>
</div>
<?php $extraScripts = ['/assets/js/auth-tabs.js']; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
