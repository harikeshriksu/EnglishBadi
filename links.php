<?php
require_once __DIR__ . '/includes/config.php';

$categories = get_categories('link');
$categorySlug = trim((string) ($_GET['category'] ?? ''));
$activeCategory = null;
foreach ($categories as $c) {
    if ($c['slug'] === $categorySlug) {
        $activeCategory = $c;
        break;
    }
}

$where = "WHERE status = 'published'";
$params = [];
if ($activeCategory) {
    $where .= ' AND category_id = ?';
    $params[] = $activeCategory['id'];
}
$stmt = db()->prepare("SELECT * FROM links {$where} ORDER BY display_order, created_at DESC");
$stmt->execute($params);
$links = $stmt->fetchAll();

$categoryNames = [];
foreach ($categories as $c) {
    $categoryNames[$c['id']] = $c['name'];
}

$pageSeo = [
    'title'       => $activeCategory ? $activeCategory['name'] . ' Links' : 'Links',
    'description' => 'Curated videos and resources to help you practice English - hand-picked and organised by topic.',
];
$activeNav = 'links';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1 class="page-title">Links</h1>
  <p class="page-subtitle">Hand-picked videos and resources worth your time.</p>

  <?php if ($categories): ?>
  <div class="chip-row">
    <a href="<?php echo e(base_url('/links')); ?>" class="chip <?php echo !$activeCategory ? 'is-active' : ''; ?>">All</a>
    <?php foreach ($categories as $c): ?>
    <a href="<?php echo e(base_url('/links') . '?category=' . urlencode($c['slug'])); ?>" class="chip <?php echo ($activeCategory && $activeCategory['id'] === $c['id']) ? 'is-active' : ''; ?>"><?php echo e($c['name']); ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$links): ?>
    <p class="muted">No links here yet. Please check back soon.</p>
  <?php else: ?>
  <div class="link-list">
    <?php foreach ($links as $l):
        $thumbUrl = upload_url($l['thumbnail']);
        $thumbWebpUrl = upload_url($l['thumbnail_webp']);
        $categoryName = $categoryNames[$l['category_id']] ?? null;
    ?>
    <div class="link-card" id="link-<?php echo (int) $l['id']; ?>">
      <?php if ($l['youtube_video_id']): ?>
        <div class="link-card__thumb" data-youtube-id="<?php echo e($l['youtube_video_id']); ?>" data-embed-target="embed-<?php echo (int) $l['id']; ?>" role="button" tabindex="0" aria-label="Play video: <?php echo e($l['name']); ?>">
          <img src="https://img.youtube.com/vi/<?php echo e($l['youtube_video_id']); ?>/hqdefault.jpg" alt="" loading="lazy">
          <span class="link-card__play"><?php echo icon('play'); ?></span>
        </div>
      <?php elseif ($thumbUrl): ?>
        <a href="<?php echo e($l['url']); ?>" target="_blank" rel="noopener noreferrer" class="link-card__thumb">
          <?php echo render_picture($thumbUrl, $thumbWebpUrl, ''); ?>
        </a>
      <?php else: ?>
        <a href="<?php echo e($l['url']); ?>" target="_blank" rel="noopener noreferrer" class="link-card__thumb">
          <span class="link-card__letter"><?php echo e(mb_strtoupper(mb_substr($l['name'], 0, 1))); ?></span>
        </a>
      <?php endif; ?>

      <div class="link-card__body">
        <?php if ($categoryName): ?><span class="link-card__category"><?php echo e($categoryName); ?></span><?php endif; ?>
        <p class="link-card__title"><?php echo e($l['name']); ?></p>
        <?php if ($l['description']): ?><p class="link-card__desc"><?php echo e($l['description']); ?></p><?php endif; ?>
        <a href="<?php echo e($l['url']); ?>" target="_blank" rel="noopener noreferrer" class="link-card__cta">
          Open link <?php echo icon('external'); ?>
        </a>
      </div>
    </div>
    <?php if ($l['youtube_video_id']): ?>
    <div class="link-embed-wrap" id="embed-<?php echo (int) $l['id']; ?>" hidden></div>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
