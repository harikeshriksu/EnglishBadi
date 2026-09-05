<?php
require_once __DIR__ . '/includes/config.php';

$categories = get_categories('poster');
$categorySlug = trim((string) ($_GET['category'] ?? ''));
$activeCategory = null;
foreach ($categories as $c) {
    if ($c['slug'] === $categorySlug) {
        $activeCategory = $c;
        break;
    }
}

$where = '';
$params = [];
if ($activeCategory) {
    $where = 'WHERE category_id = ?';
    $params[] = $activeCategory['id'];
}
$stmt = db()->prepare("SELECT * FROM posters {$where} ORDER BY display_order, created_at DESC");
$stmt->execute($params);
$posters = $stmt->fetchAll();

$lightboxData = array_map(static function ($p) {
    return [
        'image'   => upload_url($p['image_path']),
        'webp'    => upload_url($p['webp_path']),
        'caption' => $p['caption'],
        'alt'     => $p['alt_text'] ?: ($p['caption'] ?: 'Poster'),
    ];
}, $posters);

$pageSeo = [
    'title'       => $activeCategory ? $activeCategory['name'] . ' Posters' : 'Posters',
    'description' => 'Simple visual vocabulary and grammar posters you can scroll through in a minute.',
];
$activeNav = 'posters';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1 class="page-title">Posters</h1>
  <p class="page-subtitle">Tap any poster to view it full-size.</p>

  <?php if ($categories): ?>
  <div class="chip-row">
    <a href="<?php echo e(base_url('/posters')); ?>" class="chip <?php echo !$activeCategory ? 'is-active' : ''; ?>">All</a>
    <?php foreach ($categories as $c): ?>
    <a href="<?php echo e(base_url('/posters') . '?category=' . urlencode($c['slug'])); ?>" class="chip <?php echo ($activeCategory && $activeCategory['id'] === $c['id']) ? 'is-active' : ''; ?>"><?php echo e($c['name']); ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$posters): ?>
    <p class="muted">No posters here yet. Please check back soon.</p>
  <?php else: ?>
  <div class="poster-grid">
    <?php foreach ($posters as $p): ?>
    <button type="button" class="poster-grid__item" id="poster-<?php echo (int) $p['id']; ?>">
      <?php echo render_picture(upload_url($p['thumb_path']), upload_url($p['webp_thumb_path']), (string) ($p['alt_text'] ?: ($p['caption'] ?: 'Poster'))); ?>
      <?php if ($p['caption']): ?><span class="poster-grid__caption"><?php echo e($p['caption']); ?></span><?php endif; ?>
    </button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="lightbox" id="poster-lightbox" hidden role="dialog" aria-modal="true" aria-label="Poster viewer">
  <button type="button" class="lightbox__close" id="lightbox-close" aria-label="Close"><?php echo icon('close'); ?></button>
  <div class="lightbox__stage">
    <button type="button" class="lightbox__arrow lightbox__arrow--prev" id="lightbox-prev" aria-label="Previous poster"><?php echo icon('chevron-left'); ?></button>
    <picture>
      <source id="lightbox-source-webp" type="image/webp">
      <img id="lightbox-image" class="lightbox__image" src="" alt="">
    </picture>
    <button type="button" class="lightbox__arrow lightbox__arrow--next" id="lightbox-next" aria-label="Next poster"><?php echo icon('chevron-right'); ?></button>
  </div>
  <p class="lightbox__caption" id="lightbox-caption"></p>
  <p class="lightbox__counter" id="lightbox-counter"></p>
  <div class="lightbox__actions">
    <a class="btn btn--outline" id="lightbox-download" href="#" download><?php echo icon('download'); ?> Download</a>
    <button type="button" class="btn btn--outline" id="lightbox-share"><?php echo icon('share'); ?> Share</button>
  </div>
</div>
<script type="application/json" id="poster-data"><?php echo json_encode($lightboxData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>

<?php $extraScripts = ['/assets/js/lightbox.js']; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
