<?php
require_once __DIR__ . '/includes/config.php';

$slug = (string) ($_GET['slug'] ?? '');
$stmt = db()->prepare("SELECT * FROM lessons WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$lesson = $stmt->fetch();

if (!$lesson) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$categoryName = null;
if ($lesson['category_id']) {
    $cat = get_category((int) $lesson['category_id']);
    $categoryName = $cat['name'] ?? null;
}

$prevStmt = db()->prepare("SELECT slug, title FROM lessons WHERE status = 'published' AND publish_date < ? ORDER BY publish_date DESC LIMIT 1");
$prevStmt->execute([$lesson['publish_date']]);
$prevLesson = $prevStmt->fetch();

$nextStmt = db()->prepare("SELECT slug, title FROM lessons WHERE status = 'published' AND publish_date > ? ORDER BY publish_date ASC LIMIT 1");
$nextStmt->execute([$lesson['publish_date']]);
$nextLesson = $nextStmt->fetch();

$canonical = base_url('/lessons/' . $lesson['slug']);
$pageSeo = [
    'title'       => $lesson['title'],
    'description' => $lesson['meta_description'] ?: excerpt_from_html($lesson['body'], 160),
    'type'        => 'article',
    'canonical'   => $canonical,
    'image'       => upload_url($lesson['featured_image']),
];
$activeNav = 'lessons';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section" style="max-width:760px;">
  <h1 class="page-title"><?php echo e($lesson['title']); ?></h1>
  <p class="lesson-single__meta">
    <?php echo e(format_date($lesson['publish_date'])); ?>
    <?php if ($categoryName): ?> &middot; <?php echo e($categoryName); ?><?php endif; ?>
  </p>

  <?php if ($lesson['featured_image']): ?>
  <div class="lesson-single__featured">
    <?php echo render_picture(upload_url($lesson['featured_image']), upload_url($lesson['featured_image_webp']), $lesson['title'], '', false); ?>
  </div>
  <?php endif; ?>

  <div class="content-body">
    <?php echo $lesson['body']; ?>
  </div>

  <?php if ($prevLesson || $nextLesson): ?>
  <div class="lesson-single__nav">
    <?php if ($prevLesson): ?>
    <a href="<?php echo e(base_url('/lessons/' . $prevLesson['slug'])); ?>">
      <span class="dir">&larr; Previous</span>
      <span class="title"><?php echo e($prevLesson['title']); ?></span>
    </a>
    <?php else: ?><span></span><?php endif; ?>
    <?php if ($nextLesson): ?>
    <a href="<?php echo e(base_url('/lessons/' . $nextLesson['slug'])); ?>" class="lesson-single__nav--next">
      <span class="dir">Next &rarr;</span>
      <span class="title"><?php echo e($nextLesson['title']); ?></span>
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php render_json_ld(article_json_ld($lesson, $canonical)); ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
