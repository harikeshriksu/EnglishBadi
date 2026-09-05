<?php
/**
 * One-time first-run setup: creates the first (and typically only) admin
 * account through a browser form. Refuses to run again once any user
 * already exists in the `users` table.
 */
require_once __DIR__ . '/includes/config.php';

$hasUser = (bool) db()->query('SELECT id FROM users LIMIT 1')->fetch();
if ($hasUser) {
    show_friendly_error('Setup already complete', 'An admin account already exists. Please log in instead.', 403);
}

$errors = [];
$old = ['username' => '', 'display_name' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please reload the page and try again.';
    } else {
        $old['username'] = trim((string) ($_POST['username'] ?? ''));
        $old['display_name'] = trim((string) ($_POST['display_name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if ($old['username'] === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,100}$/', $old['username'])) {
            $errors[] = 'Username must be 3-100 characters (letters, numbers, and . _ - only).';
        }
        if ($old['display_name'] === '') {
            $errors[] = 'Please enter your name.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            // Re-check right before inserting, in case this form was
            // somehow opened and submitted twice at the same time.
            $hasUserNow = (bool) db()->query('SELECT id FROM users LIMIT 1')->fetch();
            if ($hasUserNow) {
                show_friendly_error('Setup already complete', 'An admin account already exists. Please log in instead.', 403);
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = db()->prepare('INSERT INTO users (username, password_hash, display_name) VALUES (?, ?, ?)');
            $stmt->execute([$old['username'], $hash, $old['display_name']]);

            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) db()->lastInsertId();
            $_SESSION['admin_last_activity'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            flash_set('success', 'Your admin account has been created. Welcome!');
            redirect(base_url('/admin/dashboard.php'));
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Set Up Your Admin Account - English Badi</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?php echo e(base_url('/admin/assets/admin.css')); ?>">
</head>
<body class="admin-body">
<div class="admin-center-screen">
  <div class="admin-login-card" style="max-width:440px;">
    <div class="admin-login-card__logo"><?php echo icon('logo'); ?></div>
    <h1>Set Up English Badi</h1>
    <p class="sub">Create your admin account. This page only works once - after your account exists, use /admin to log in.</p>

    <?php if ($errors): ?>
    <div class="flash flash--error">
      <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(base_url('/setup.php')); ?>">
      <?php echo csrf_field(); ?>
      <div class="form-field">
        <label for="display_name">Your name</label>
        <input type="text" id="display_name" name="display_name" required value="<?php echo e($old['display_name']); ?>">
      </div>
      <div class="form-field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?php echo e($old['username']); ?>" autocomplete="username">
        <p class="form-hint">Letters, numbers, and . _ - only. You'll use this to log in.</p>
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
        <p class="form-hint">At least 8 characters.</p>
      </div>
      <div class="form-field">
        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn--primary btn--block">Create admin account</button>
    </form>
  </div>
</div>
</body>
</html>
