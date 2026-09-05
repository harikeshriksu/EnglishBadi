<?php
require_once __DIR__ . '/includes/config.php';

$categories = get_categories('lesson');
$categorySlug = trim((string) ($_GET['category'] ?? ''));
$activeCategory = null;
foreach ($categories as $c) {
    if ($c['slug'] === $categorySlug) {
        $activeCategory = $c;
        break;
    }
}

$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = "WHERE status = 'published'";
$params = [];
if ($activeCategory) {
    $where .= ' AND category_id = ?';
    $params[] = $activeCategory['id'];
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM lessons {$where}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare("SELECT * FROM lessons {$where} ORDER BY publish_date DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$lessons = $stmt->fetchAll();

$urlBase = base_url('/lessons') . '?' . ($activeCategory ? 'category=' . urlencode($activeCategory['slug']) . '&' : '') . 'page={page}';

$pageSeo = [
    'title'       => $activeCategory ? $activeCategory['name'] . ' Lessons' : 'Lessons',
    'description' => 'Practical English lessons for Telugu speakers' . ($activeCategory ? ' - ' . $activeCategory['name'] : '') . '. Grammar, vocabulary, spoken English and more.',
];
$activeNav = 'lessons';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1 class="page-title">Lessons</h1>
  <p class="page-subtitle">Short, practical English lessons you can read in a few minutes.</p>

  <div class="chip-row">
    <a href="<?php echo e(base_url('/lessons')); ?>" class="chip <?php echo !$activeCategory ? 'is-active' : ''; ?>">All</a>
    <?php foreach ($categories as $c): ?>
    <a href="<?php echo e(base_url('/lessons') . '?category=' . urlencode($c['slug'])); ?>" class="chip <?php echo ($activeCategory && $activeCategory['id'] === $c['id']) ? 'is-active' : ''; ?>"><?php echo e($c['name']); ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$lessons): ?>
    <p class="muted">No lessons here yet. Please check back soon.</p>
  <?php else: ?>
  <div class="lesson-list">
    <?php foreach ($lessons as $lesson):
        $excerptText = $lesson['excerpt'] !== null && trim((string) $lesson['excerpt']) !== '' ? $lesson['excerpt'] : excerpt_from_html($lesson['body'], 160);
        $thumbUrl = upload_url($lesson['featured_thumb']);
    ?>
    <a href="<?php echo e(base_url('/lessons/' . $lesson['slug'])); ?>" class="lesson-list__item">
      <div class="lesson-list__thumb">
        <?php if ($thumbUrl): ?>
          <?php echo render_picture($thumbUrl, upload_url($lesson['featured_thumb_webp']), ''); ?>
        <?php else: ?>
          <?php echo icon('book'); ?>
        <?php endif; ?>
      </div>
      <div class="lesson-list__body">
        <p class="lesson-list__title"><?php echo e($lesson['title']); ?></p>
        <p class="lesson-list__excerpt"><?php echo e($excerptText); ?></p>
        <p class="lesson-list__meta"><?php echo e(format_date($lesson['publish_date'])); ?></p>
        <span class="lesson-list__readmore">Read More &rarr;</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <?php echo pager_links($page, $totalPages, $urlBase); ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
