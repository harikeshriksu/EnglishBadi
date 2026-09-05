<?php
require_once __DIR__ . '/../includes/config.php';

if (is_admin_logged_in()) {
    redirect(base_url('/admin/dashboard.php'));
}

$hasUser = (bool) db()->query('SELECT id FROM users LIMIT 1')->fetch();
if (!$hasUser) {
    redirect(base_url('/setup.php'));
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your form session expired. Please try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $result = admin_login_attempt($username, $password);
        if ($result['ok']) {
            redirect(base_url('/admin/dashboard.php'));
        }
        $error = $result['error'];
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login - English Badi</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?php echo e(base_url('/admin/assets/admin.css')); ?>">
</head>
<body class="admin-body">
<div class="admin-center-screen">
  <div class="admin-login-card">
    <div class="admin-login-card__logo"><?php echo icon('logo'); ?></div>
    <h1>English Badi Admin</h1>
    <p class="sub">Log in to manage lessons, links, posters and quizzes.</p>

    <?php if ($error): ?><div class="flash flash--error"><?php echo e($error); ?></div><?php endif; ?>

    <form method="post" action="<?php echo e(base_url('/admin/index.php')); ?>">
      <?php echo csrf_field(); ?>
      <div class="form-field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus autocomplete="username">
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn--primary btn--block">Log in</button>
    </form>
  </div>
</div>
</body>
</html>
