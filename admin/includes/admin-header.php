<?php
/**
 * Shared admin layout opening. Each admin page sets $adminPageTitle and
 * $activeAdminNav before including this file. Set $loadPublicCss = true
 * on pages that preview public-site rich text (lesson-form, page-form)
 * so the preview uses the exact same .content-body rules as the live site.
 */

$adminPageTitle = $adminPageTitle ?? 'Admin';
$activeAdminNav = $activeAdminNav ?? '';
$loadPublicCss = $loadPublicCss ?? false;
$flash = flash_get();

$adminNavItems = [
    'dashboard'  => ['dashboard.php', 'Dashboard'],
    'lessons'    => ['lessons.php', 'Lessons'],
    'links'      => ['links.php', 'Links'],
    'posters'    => ['posters.php', 'Posters'],
    'quizzes'    => ['quizzes.php', 'Quizzes'],
    'pages'      => ['pages.php', 'Pages'],
    'categories' => ['categories.php', 'Categories'],
    'users'      => ['users.php', 'Registered Users'],
    'messages'   => ['messages.php', 'Messages'],
    'settings'   => ['settings.php', 'Settings'],
];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($adminPageTitle); ?> - English Badi Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="data:image/svg+xml,<?php echo rawurlencode(icon('logo')); ?>">
<link rel="stylesheet" href="<?php echo e(base_url('/admin/assets/admin.css')); ?>">
<?php if ($loadPublicCss): ?>
<link rel="stylesheet" href="<?php echo e(base_url('/assets/css/style.css')); ?>">
<?php endif; ?>
</head>
<body class="admin-body">
<header class="admin-header">
  <div class="admin-header__inner">
    <a href="<?php echo e(base_url('/admin/dashboard.php')); ?>" class="admin-logo">
      <?php echo icon_html('logo', 'admin-logo__mark'); ?>
      <span>English Badi <small>Admin</small></span>
    </a>
    <button type="button" class="admin-hamburger" id="admin-hamburger" aria-expanded="false" aria-controls="admin-nav" aria-label="Menu">
      <?php echo icon('menu'); ?>
    </button>
    <div class="admin-header__actions">
      <a href="<?php echo e(base_url('/')); ?>" target="_blank" rel="noopener" class="admin-link-btn">View site</a>
      <span class="admin-user"><?php echo e($currentAdmin['display_name'] ?? ''); ?></span>
      <a href="<?php echo e(base_url('/admin/logout.php')); ?>" class="admin-link-btn admin-link-btn--danger">Logout</a>
    </div>
  </div>
</header>
<div class="admin-shell">
  <nav class="admin-nav" id="admin-nav" aria-label="Admin menu">
    <?php foreach ($adminNavItems as $key => [$href, $label]): ?>
    <a href="<?php echo e(base_url('/admin/' . $href)); ?>" class="<?php echo $activeAdminNav === $key ? 'is-active' : ''; ?>"><?php echo e($label); ?></a>
    <?php endforeach; ?>
  </nav>
  <main class="admin-main">
    <?php if ($flash): ?><div class="flash flash--<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div><?php endif; ?>
