<?php
require_once __DIR__ . '/includes/config.php';

$q = trim((string) ($_GET['q'] ?? ''));
$results = ['lessons' => [], 'links' => [], 'posters' => [], 'quizzes' => []];

if ($q !== '') {
    $like = '%' . $q . '%';

    $stmt = db()->prepare(
        "SELECT id, title, slug, excerpt FROM lessons
         WHERE status = 'published' AND (title LIKE ? OR body LIKE ?)
         ORDER BY publish_date DESC LIMIT 20"
    );
    $stmt->execute([$like, $like]);
    $results['lessons'] = $stmt->fetchAll();

    $stmt = db()->prepare(
        "SELECT id, name, description FROM links
         WHERE status = 'published' AND (name LIKE ? OR description LIKE ?)
         ORDER BY display_order LIMIT 20"
    );
    $stmt->execute([$like, $like]);
    $results['links'] = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT id, caption FROM posters WHERE caption LIKE ? ORDER BY created_at DESC LIMIT 20');
    $stmt->execute([$like]);
    $results['posters'] = $stmt->fetchAll();

    $stmt = db()->prepare(
        "SELECT id, title, slug, description FROM quizzes
         WHERE status = 'published' AND title LIKE ? ORDER BY created_at DESC LIMIT 20"
    );
    $stmt->execute([$like]);
    $results['quizzes'] = $stmt->fetchAll();
}

$totalResults = array_sum(array_map('count', $results));

$pageSeo = [
    'title'       => $q !== '' ? 'Search results for "' . $q . '"' : 'Search',
    'description' => 'Search English Badi lessons, links, posters and quizzes.',
    'noindex'     => true,
];
$activeNav = '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1 class="page-title">Search</h1>
  <?php if ($q === ''): ?>
    <p class="page-subtitle">Use the search box in the menu to search lessons, links, posters and quizzes.</p>
  <?php else: ?>
    <p class="page-subtitle"><?php echo (int) $totalResults; ?> result<?php echo $totalResults === 1 ? '' : 's'; ?> for &ldquo;<?php echo e($q); ?>&rdquo;</p>

    <?php if ($results['lessons']): ?>
    <div class="search-results-group">
      <h2>Lessons</h2>
      <?php foreach ($results['lessons'] as $r): ?>
      <div class="search-result-item">
        <a href="<?php echo e(base_url('/lessons/' . $r['slug'])); ?>"><?php echo e($r['title']); ?></a>
        <p><?php echo e(excerpt_from_html((string) $r['excerpt'], 140)); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($results['links']): ?>
    <div class="search-results-group">
      <h2>Links</h2>
      <?php foreach ($results['links'] as $r): ?>
      <div class="search-result-item">
        <a href="<?php echo e(base_url('/links#link-' . $r['id'])); ?>"><?php echo e($r['name']); ?></a>
        <p><?php echo e(excerpt_from_html((string) $r['description'], 140)); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($results['posters']): ?>
    <div class="search-results-group">
      <h2>Posters</h2>
      <?php foreach ($results['posters'] as $r): ?>
      <div class="search-result-item">
        <a href="<?php echo e(base_url('/posters#poster-' . $r['id'])); ?>"><?php echo e($r['caption'] ?: 'Poster'); ?></a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($results['quizzes']): ?>
    <div class="search-results-group">
      <h2>Quizzes</h2>
      <?php foreach ($results['quizzes'] as $r): ?>
      <div class="search-result-item">
        <a href="<?php echo e(base_url('/quiz/' . $r['slug'])); ?>"><?php echo e($r['title']); ?></a>
        <p><?php echo e(excerpt_from_html((string) $r['description'], 140)); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($totalResults === 0): ?>
      <p class="muted">No results found. Try different keywords.</p>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
