<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$settingKeys = ['site_title', 'site_tagline', 'homepage_intro', 'contact_email', 'social_instagram', 'social_youtube', 'social_facebook', 'meta_description_default'];

$errors = [];
$passwordErrors = [];
$passwordSuccess = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $formType = $_POST['form'] ?? '';

        if ($formType === 'settings') {
            $db = db();
            foreach ($settingKeys as $key) {
                $value = trim((string) ($_POST[$key] ?? ''));
                $stmt = $db->prepare(
                    'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
                );
                $stmt->execute([$key, $value]);
            }
            flash_set('success', 'Settings saved.');
            redirect(base_url('/admin/settings.php'));
        } elseif ($formType === 'password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['new_password_confirm'] ?? '');

            $admin = current_admin();
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$admin['id']]);
            $userRow = $stmt->fetch();

            if (!$userRow || !password_verify($current, $userRow['password_hash'])) {
                $passwordErrors[] = 'Your current password is incorrect.';
            } elseif (mb_strlen($new) < 8) {
                $passwordErrors[] = 'Your new password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $passwordErrors[] = 'New passwords do not match.';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $admin['id']]);
                $passwordSuccess = true;
            }
        }
    }
}

$currentSettings = all_settings();

$adminPageTitle = 'Settings';
$activeAdminNav = 'settings';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header"><h1>Settings</h1></div>

<?php if ($errors): ?>
<div class="flash flash--error"><?php foreach ($errors as $err): ?><p style="margin:0"><?php echo e($err); ?></p><?php endforeach; ?></div>
<?php endif; ?>

<div class="admin-card" style="margin-bottom:16px;">
  <h2>Site settings</h2>
  <form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="form" value="settings">
    <div class="form-field">
      <label for="site_title">Site title</label>
      <input type="text" id="site_title" name="site_title" value="<?php echo e($currentSettings['site_title'] ?? ''); ?>" maxlength="150">
    </div>
    <div class="form-field">
      <label for="site_tagline">Tagline</label>
      <input type="text" id="site_tagline" name="site_tagline" value="<?php echo e($currentSettings['site_tagline'] ?? ''); ?>" maxlength="255">
    </div>
    <div class="form-field">
      <label for="homepage_intro">Homepage introduction text</label>
      <textarea id="homepage_intro" name="homepage_intro" rows="3"><?php echo e($currentSettings['homepage_intro'] ?? ''); ?></textarea>
    </div>
    <div class="form-field">
      <label for="meta_description_default">Default search description</label>
      <textarea id="meta_description_default" name="meta_description_default" rows="2" maxlength="300"><?php echo e($currentSettings['meta_description_default'] ?? ''); ?></textarea>
      <p class="form-hint">Used when a page doesn't have its own description.</p>
    </div>
    <div class="form-field">
      <label for="contact_email">Contact form recipient email</label>
      <input type="email" id="contact_email" name="contact_email" value="<?php echo e($currentSettings['contact_email'] ?? ''); ?>">
      <p class="form-hint">Where messages from the Contact page are sent.</p>
    </div>
    <div class="form-row">
      <div class="form-field">
        <label for="social_instagram">Instagram URL</label>
        <input type="url" id="social_instagram" name="social_instagram" value="<?php echo e($currentSettings['social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/...">
      </div>
      <div class="form-field">
        <label for="social_youtube">YouTube URL</label>
        <input type="url" id="social_youtube" name="social_youtube" value="<?php echo e($currentSettings['social_youtube'] ?? ''); ?>" placeholder="https://youtube.com/...">
      </div>
      <div class="form-field">
        <label for="social_facebook">Facebook URL</label>
        <input type="url" id="social_facebook" name="social_facebook" value="<?php echo e($currentSettings['social_facebook'] ?? ''); ?>" placeholder="https://facebook.com/...">
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Save Settings</button>
    </div>
  </form>
</div>

<div class="admin-card">
  <h2>Change admin password</h2>
  <?php if ($passwordSuccess): ?><div class="flash flash--success">Password updated.</div><?php endif; ?>
  <?php if ($passwordErrors): ?>
  <div class="flash flash--error"><?php foreach ($passwordErrors as $err): ?><p style="margin:0"><?php echo e($err); ?></p><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="form" value="password">
    <div class="form-field">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="form-field">
      <label for="new_password">New password</label>
      <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
    </div>
    <div class="form-field">
      <label for="new_password_confirm">Confirm new password</label>
      <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8" autocomplete="new-password">
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn--primary">Change Password</button>
    </div>
  </form>
</div>
<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
