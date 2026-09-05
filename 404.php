<?php
require_once __DIR__ . '/includes/config.php';

http_response_code(404);

$pageSeo = [
    'title'       => 'Page Not Found',
    'description' => 'The page you were looking for could not be found.',
    'noindex'     => true,
];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container error-page">
  <p class="error-page__code">404</p>
  <h1 class="page-title">Page not found</h1>
  <p class="page-subtitle">Sorry, we couldn't find that page. It may have been moved or removed.</p>
  <div class="error-page__links">
    <a href="<?php echo e(base_url('/lessons')); ?>" class="btn btn--outline">Lessons</a>
    <a href="<?php echo e(base_url('/links')); ?>" class="btn btn--outline">Links</a>
    <a href="<?php echo e(base_url('/posters')); ?>" class="btn btn--outline">Posters</a>
    <a href="<?php echo e(base_url('/quizzes')); ?>" class="btn btn--outline">Quizzes</a>
  </div>
  <p style="margin-top:20px;"><a href="<?php echo e(base_url('/')); ?>">&larr; Back to homepage</a></p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
