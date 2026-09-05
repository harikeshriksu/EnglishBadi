<?php
/**
 * Shared public-site header/layout opening. Each page sets $pageSeo
 * (array for render_seo_head) and $activeNav (one of: home, start-here,
 * lessons, links, posters, quizzes, about, contact) before including
 * this file.
 */

$pageSeo = $pageSeo ?? [];
$activeNav = $activeNav ?? '';
$navItems = [
    'home'       => ['/', 'Home'],
    'start-here' => ['/start-here', 'Start Here'],
    'lessons'    => ['/lessons', 'Lessons'],
    'links'      => ['/links', 'Links'],
    'posters'    => ['/posters', 'Posters'],
    'quizzes'    => ['/quizzes', 'Quizzes'],
    'about'      => ['/about', 'About'],
    'contact'    => ['/contact', 'Contact'],
];
$flash = flash_get();
$learner = current_learner();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php render_seo_head($pageSeo); ?>
<link rel="icon" href="data:image/svg+xml,<?php echo rawurlencode(icon('logo')); ?>">
<link rel="stylesheet" href="<?php echo e(base_url('/assets/css/style.css')); ?>">
</head>
<body>
<a href="#main-content" class="visually-hidden">Skip to main content</a>
<header class="site-header">
  <div class="site-header__inner">
    <a href="<?php echo e(base_url('/')); ?>" class="site-logo" aria-label="English Badi home">
      <?php echo icon_html('logo', 'site-logo__mark'); ?>
      <span>English Badi</span>
    </a>

    <nav class="site-nav" id="site-nav" aria-label="Main menu">
      <form class="site-search" action="<?php echo e(base_url('/search')); ?>" method="get" role="search">
        <label for="site-search-input" class="visually-hidden">Search the site</label>
        <input type="search" id="site-search-input" name="q" placeholder="Search lessons, links, quizzes..." value="<?php echo e($_GET['q'] ?? ''); ?>">
        <button type="submit" aria-label="Search"><?php echo icon('search'); ?></button>
      </form>
      <?php if (!$learner): ?>
      <a href="<?php echo e(base_url('/login')); ?>" class="site-nav__login-mobile">Login</a>
      <?php endif; ?>
      <?php foreach ($navItems as $key => [$href, $label]): ?>
      <a href="<?php echo e(base_url($href)); ?>" class="<?php echo $activeNav === $key ? 'is-active' : ''; ?>"><?php echo e($label); ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="site-header__actions">
      <?php if ($learner): ?>
        <a href="<?php echo e(base_url('/my-progress')); ?>" class="auth-btn auth-btn--user"><?php echo e($learner['name']); ?></a>
      <?php else: ?>
        <a href="<?php echo e(base_url('/login')); ?>" class="auth-btn auth-btn--login-desktop">Login</a>
        <a href="<?php echo e(base_url('/register')); ?>" class="auth-btn auth-btn--primary">Register</a>
      <?php endif; ?>
      <button type="button" class="hamburger" id="hamburger-btn" aria-expanded="false" aria-controls="site-nav" aria-label="Open menu">
        <?php echo icon('menu'); ?>
      </button>
    </div>
  </div>
</header>
<main id="main-content">
<?php if ($flash): ?>
  <div class="container">
    <div class="flash flash--<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
  </div>
<?php endif; ?>
