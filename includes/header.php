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
    // 'start-here' hidden for now (not deleted - start-here.php still
    // works if linked directly, just not in the nav while we try the
    // site without it).
    'lessons'    => ['/lessons', 'Lessons'],
    'links'      => ['/links', 'Video Lessons'],
    'posters'    => ['/posters', 'Posters'],
    'quizzes'    => ['/quizzes', 'Quizzes'],
    'about'      => ['/about', 'About'],
    // 'contact' merged into the About page (see about.php) - contact.php
    // now just redirects there, so it's not a separate nav destination.
];
$flash = flash_get();
$learner = current_learner();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php render_seo_head($pageSeo); ?>
<link rel="icon" href="<?php echo e(asset_url('/favicon.ico')); ?>" sizes="any">
<link rel="apple-touch-icon" href="<?php echo e(asset_url('/apple-touch-icon.png')); ?>">
<link rel="stylesheet" href="<?php echo e(asset_url('/assets/css/style.css')); ?>">
</head>
<body>
<a href="#main-content" class="visually-hidden">Skip to main content</a>
<header class="site-header">
  <!-- Compact single-bar layout: phone and tablet widths, where there
       isn't room for a full two-strip header. Unchanged from before -
       nav + search live in the off-canvas drawer, toggled by the
       hamburger. Hidden from >=1200px, where the two-strip layout below
       takes over instead. -->
  <div class="site-header__inner site-header__inner--compact">
    <a href="<?php echo e(base_url('/')); ?>" class="site-logo" aria-label="English Badi home">
      <img src="<?php echo e(asset_url('/assets/img/logo-header.png')); ?>" alt="" class="site-logo__mark">
      <span>English Badi</span>
    </a>

    <nav class="site-nav" id="site-nav" aria-label="Main menu">
      <form class="site-search" action="<?php echo e(base_url('/search')); ?>" method="get" role="search">
        <label for="site-search-input" class="visually-hidden">Search the site</label>
        <input type="search" id="site-search-input" name="q" placeholder="Search lessons, video lessons, quizzes..." value="<?php echo e($_GET['q'] ?? ''); ?>">
        <button type="submit" aria-label="Search"><?php echo icon('search'); ?></button>
      </form>
      <?php foreach ($navItems as $key => [$href, $label]): ?>
      <a href="<?php echo e(base_url($href)); ?>" class="<?php echo $activeNav === $key ? 'is-active' : ''; ?>"><?php echo e($label); ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="site-header__actions">
      <?php if ($learner): ?>
        <a href="<?php echo e(base_url('/my-progress')); ?>" class="auth-btn auth-btn--user"><?php echo e($learner['name']); ?></a>
      <?php else: ?>
        <a href="<?php echo e(base_url('/login')); ?>" class="auth-btn auth-btn--primary">Login / Register</a>
      <?php endif; ?>
      <button type="button" class="hamburger" id="hamburger-btn" aria-expanded="false" aria-controls="site-nav" aria-label="Open menu">
        <?php echo icon('menu'); ?>
      </button>
    </div>
  </div>

  <!-- Two-strip layout: >=1200px only (see .site-header__top/__bottom in
       style.css). Top strip: wordmark left, search + Sign up right.
       Bottom strip: the full nav row, on its own related-but-distinct
       background colour. -->
  <div class="site-header__top">
    <div class="site-header__top-inner">
      <a href="<?php echo e(base_url('/')); ?>" class="site-logo site-logo--lg" aria-label="English Badi home">
        <img src="<?php echo e(asset_url('/assets/img/logo-header.png')); ?>" alt="" class="site-logo__mark">
        <span>English Badi</span>
      </a>
      <div class="site-header__top-actions">
        <form class="site-search" action="<?php echo e(base_url('/search')); ?>" method="get" role="search">
          <label for="site-search-input-desktop" class="visually-hidden">Search the site</label>
          <input type="search" id="site-search-input-desktop" name="q" placeholder="Search lessons, video lessons, quizzes..." value="<?php echo e($_GET['q'] ?? ''); ?>">
          <button type="submit" aria-label="Search"><?php echo icon('search'); ?></button>
        </form>
        <?php if ($learner): ?>
          <a href="<?php echo e(base_url('/my-progress')); ?>" class="auth-btn auth-btn--user"><?php echo e($learner['name']); ?></a>
        <?php else: ?>
          <a href="<?php echo e(base_url('/login')); ?>" class="auth-btn auth-btn--primary">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="site-header__bottom">
    <div class="site-header__bottom-inner">
      <nav class="site-nav-desktop" aria-label="Main menu">
        <?php foreach ($navItems as $key => [$href, $label]): ?>
        <a href="<?php echo e(base_url($href)); ?>" class="<?php echo $activeNav === $key ? 'is-active' : ''; ?>"><?php echo e($label); ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
</header>
<main id="main-content">
<?php if ($flash): ?>
  <div class="container">
    <div class="flash flash--<?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
  </div>
<?php endif; ?>
