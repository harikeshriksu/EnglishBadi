<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

$counts = [
    'lessons' => (int) db()->query('SELECT COUNT(*) FROM lessons')->fetchColumn(),
    'links'   => (int) db()->query('SELECT COUNT(*) FROM links')->fetchColumn(),
    'posters' => (int) db()->query('SELECT COUNT(*) FROM posters')->fetchColumn(),
    'quizzes' => (int) db()->query('SELECT COUNT(*) FROM quizzes')->fetchColumn(),
];
$unreadMessages = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
$learnerCount = (int) db()->query('SELECT COUNT(*) FROM learners')->fetchColumn();

$recentLessons = db()->query('SELECT id, title, status FROM lessons ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$recentLinks = db()->query('SELECT id, name, status FROM links ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$recentPosters = db()->query('SELECT id, caption, created_at FROM posters ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recentQuizzes = db()->query('SELECT id, title, status FROM quizzes ORDER BY updated_at DESC LIMIT 5')->fetchAll();

$adminPageTitle = 'Dashboard';
$activeAdminNav = 'dashboard';
require_once __DIR__ . '/includes/admin-header.php';
?>
<div class="admin-page-header">
  <h1>Dashboard</h1>
</div>

<div class="admin-quick-add">
  <a href="<?php echo e(base_url('/admin/lesson-form.php')); ?>"><?php echo icon('plus'); ?> Add a lesson</a>
  <a href="<?php echo e(base_url('/admin/link-form.php')); ?>"><?php echo icon('plus'); ?> Add a link</a>
  <a href="<?php echo e(base_url('/admin/posters.php')); ?>"><?php echo icon('plus'); ?> Add a poster</a>
  <a href="<?php echo e(base_url('/admin/quiz-form.php')); ?>"><?php echo icon('plus'); ?> Add a quiz</a>
</div>

<div class="admin-stats">
  <div class="admin-stat"><p class="admin-stat__value"><?php echo $counts['lessons']; ?></p><p class="admin-stat__label">Lessons</p></div>
  <div class="admin-stat"><p class="admin-stat__value"><?php echo $counts['links']; ?></p><p class="admin-stat__label">Links</p></div>
  <div class="admin-stat"><p class="admin-stat__value"><?php echo $counts['posters']; ?></p><p class="admin-stat__label">Posters</p></div>
  <div class="admin-stat"><p class="admin-stat__value"><?php echo $counts['quizzes']; ?></p><p class="admin-stat__label">Quizzes</p></div>
</div>

<div class="admin-stats">
  <div class="admin-stat"><p class="admin-stat__value"><?php echo $unreadMessages; ?></p><p class="admin-stat__label">Unread Messages</p></div>
  <div class="admin-stat"><p class="admin-stat__value"><?php echo $learnerCount; ?></p><p class="admin-stat__label">Registered Learners</p></div>
</div>

<div class="admin-dash-columns">
  <div class="admin-card">
    <h2>Recent lessons</h2>
    <?php if (!$recentLessons): ?><p class="admin-empty">No lessons yet.</p><?php else: foreach ($recentLessons as $l): ?>
      <p><a href="<?php echo e(base_url('/admin/lesson-form.php?id=' . $l['id'])); ?>"><?php echo e($l['title']); ?></a> <span class="badge badge--<?php echo e($l['status']); ?>"><?php echo e($l['status']); ?></span></p>
    <?php endforeach; endif; ?>
  </div>
  <div class="admin-card">
    <h2>Recent links</h2>
    <?php if (!$recentLinks): ?><p class="admin-empty">No links yet.</p><?php else: foreach ($recentLinks as $l): ?>
      <p><a href="<?php echo e(base_url('/admin/link-form.php?id=' . $l['id'])); ?>"><?php echo e($l['name']); ?></a> <span class="badge badge--<?php echo e($l['status']); ?>"><?php echo e($l['status']); ?></span></p>
    <?php endforeach; endif; ?>
  </div>
  <div class="admin-card">
    <h2>Recent posters</h2>
    <?php if (!$recentPosters): ?><p class="admin-empty">No posters yet.</p><?php else: foreach ($recentPosters as $p): ?>
      <p><?php echo e($p['caption'] ?: 'Untitled poster'); ?> &middot; <?php echo e(format_date($p['created_at'])); ?></p>
    <?php endforeach; endif; ?>
  </div>
  <div class="admin-card">
    <h2>Recent quizzes</h2>
    <?php if (!$recentQuizzes): ?><p class="admin-empty">No quizzes yet.</p><?php else: foreach ($recentQuizzes as $q): ?>
      <p><a href="<?php echo e(base_url('/admin/quiz-form.php?id=' . $q['id'])); ?>"><?php echo e($q['title']); ?></a> <span class="badge badge--<?php echo e($q['status']); ?>"><?php echo e($q['status']); ?></span></p>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
