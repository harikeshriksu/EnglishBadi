<?php
require_once __DIR__ . '/includes/config.php';

$stmt = db()->prepare('SELECT * FROM pages WHERE slug = ?');
$stmt->execute(['start-here']);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pageSeo = [
    'title'       => $page['title'],
    'description' => $page['meta_description'] ?: excerpt_from_html($page['body'], 160),
];
$activeNav = 'start-here';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" style="max-width:760px;">
  <h1 class="page-title"><?php echo e($page['title']); ?></h1>
  <div class="content-body">
    <?php echo $page['body']; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
