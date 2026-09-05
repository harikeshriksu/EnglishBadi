<?php
require_once __DIR__ . '/includes/config.php';

if (is_learner_logged_in()) {
    redirect(base_url('/my-progress'));
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your form session expired. Please try again.';
    } else {
        $old['name'] = trim((string) ($_POST['name'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $result = learner_register($old['name'], $old['email'], $password);
        if ($result['ok']) {
            flash_set('success', 'Welcome! Your account has been created.');
            redirect(base_url('/my-progress'));
        }
        $errors[] = $result['error'];
    }
}

$pageSeo = [
    'title'       => 'Register',
    'description' => 'Create a free English Badi account to track your quiz scores over time.',
];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <div class="form-card">
    <h1 class="page-title text-center">Register</h1>
    <p class="page-subtitle text-center">Optional, and free. Track your quiz scores over time.</p>

    <?php if ($errors): ?>
    <div class="flash flash--error">
      <?php foreach ($errors as $err): ?><p style="margin:0 0 4px;"><?php echo e($err); ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(base_url('/register')); ?>">
      <?php echo csrf_field(); ?>
      <div class="form-field">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required maxlength="150" value="<?php echo e($old['name']); ?>">
      </div>
      <div class="form-field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required maxlength="190" value="<?php echo e($old['email']); ?>">
      </div>
      <div class="form-field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="6">
        <p class="form-hint">At least 6 characters.</p>
      </div>
      <button type="submit" class="btn btn--primary btn--block">Create account</button>
    </form>
    <p class="text-center" style="margin-top:16px;"><a href="<?php echo e(base_url('/login')); ?>">Already have an account? Log in</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
