<?php
require_once __DIR__ . '/includes/config.php';

$db = db();

/**
 * Shared by the homepage's "Latest content" and "Popular content" strips -
 * both show the same six-item, newest-first mix of all four content types,
 * differing only in whether they require is_popular = 1.
 */
function fetch_homepage_feed(PDO $db, bool $popularOnly, int $limit): array
{
    $statusExtra = $popularOnly ? ' AND is_popular = 1' : '';
    $postersWhere = $popularOnly ? 'WHERE is_popular = 1' : '';

    $lessons = $db->query(
        "SELECT id, title, slug, featured_thumb, featured_thumb_webp, publish_date AS item_date
         FROM lessons WHERE status = 'published'{$statusExtra} ORDER BY publish_date DESC LIMIT {$limit}"
    )->fetchAll();
    foreach ($lessons as &$r) {
        $r['type'] = 'Lesson';
        $r['url'] = base_url('/lessons/' . $r['slug']);
        $r['title_display'] = $r['title'];
        $r['thumb'] = upload_url($r['featured_thumb']);
        $r['thumb_webp'] = upload_url($r['featured_thumb_webp']);
    }
    unset($r);

    $links = $db->query(
        "SELECT id, name, thumbnail, thumbnail_webp, youtube_video_id, created_at AS item_date
         FROM links WHERE status = 'published'{$statusExtra} ORDER BY created_at DESC LIMIT {$limit}"
    )->fetchAll();
    foreach ($links as &$r) {
        $r['type'] = 'Link';
        $r['url'] = base_url('/links#link-' . $r['id']);
        $r['title_display'] = $r['name'];
        if ($r['youtube_video_id']) {
            $r['thumb'] = 'https://img.youtube.com/vi/' . $r['youtube_video_id'] . '/hqdefault.jpg';
            $r['thumb_webp'] = null;
        } else {
            $r['thumb'] = upload_url($r['thumbnail']);
            $r['thumb_webp'] = upload_url($r['thumbnail_webp']);
        }
    }
    unset($r);

    $posters = $db->query(
        "SELECT id, caption, thumb_path, webp_thumb_path, created_at AS item_date
         FROM posters {$postersWhere} ORDER BY created_at DESC LIMIT {$limit}"
    )->fetchAll();
    foreach ($posters as &$r) {
        $r['type'] = 'Poster';
        $r['url'] = base_url('/posters#poster-' . $r['id']);
        $r['title_display'] = $r['caption'] ?: 'Poster';
        $r['thumb'] = upload_url($r['thumb_path']);
        $r['thumb_webp'] = upload_url($r['webp_thumb_path']);
    }
    unset($r);

    $quizzes = $db->query(
        "SELECT id, title, slug, created_at AS item_date
         FROM quizzes WHERE status = 'published'{$statusExtra} ORDER BY created_at DESC LIMIT {$limit}"
    )->fetchAll();
    foreach ($quizzes as &$r) {
        $r['type'] = 'Quiz';
        $r['url'] = base_url('/quiz/' . $r['slug']);
        $r['title_display'] = $r['title'];
        $r['thumb'] = null;
        $r['thumb_webp'] = null;
    }
    unset($r);

    $items = array_merge($lessons, $links, $posters, $quizzes);
    usort($items, static fn ($a, $b) => strtotime((string) $b['item_date']) <=> strtotime((string) $a['item_date']));
    return array_slice($items, 0, $limit);
}

$latest = fetch_homepage_feed($db, false, 6);
$popular = fetch_homepage_feed($db, true, 6);

$pageSeo = [
    'title'       => '',
    'description' => setting('homepage_intro', setting('meta_description_default')),
];
$activeNav = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<div class="home-hero">
  <div class="home-tiles">
    <a href="<?php echo e(base_url('/lessons')); ?>" class="tile tile--lessons">
      <?php echo icon_html('book', 'tile__icon'); ?>
      <span class="tile__label">Lessons</span>
      <span class="tile__subtitle" lang="te">పాఠాలు చదవండి</span>
    </a>
    <a href="<?php echo e(base_url('/links')); ?>" class="tile tile--links">
      <?php echo icon_html('link', 'tile__icon'); ?>
      <span class="tile__label">Links</span>
      <span class="tile__subtitle" lang="te">వీడియోలు &amp; వనరులు</span>
    </a>
    <a href="<?php echo e(base_url('/posters')); ?>" class="tile tile--posters">
      <?php echo icon_html('image', 'tile__icon'); ?>
      <span class="tile__label">Posters</span>
      <span class="tile__subtitle" lang="te">చిత్ర పాఠాలు</span>
    </a>
    <a href="<?php echo e(base_url('/quizzes')); ?>" class="tile tile--quizzes">
      <?php echo icon_html('check-circle', 'tile__icon'); ?>
      <span class="tile__label">Quizzes</span>
      <span class="tile__subtitle" lang="te">సాధన పరీక్షలు</span>
    </a>
  </div>
</div>

<div class="home-intro container">
  <h1 class="visually-hidden"><?php echo e(setting('site_title', 'English Badi')); ?></h1>
  <p><?php echo e(setting('homepage_intro')); ?></p>
  <a href="<?php echo e(base_url('/start-here')); ?>" class="btn btn--primary">Start Here</a>
</div>

<?php if ($latest): ?>
<section class="latest-strip container">
  <h2>Latest content</h2>
  <div class="latest-grid">
    <?php foreach ($latest as $item): ?>
    <a class="latest-card" href="<?php echo e($item['url']); ?>">
      <div class="latest-card__thumb">
        <span class="latest-card__type"><?php echo e($item['type']); ?></span>
        <?php if ($item['thumb']): ?>
          <?php echo render_picture($item['thumb'], $item['thumb_webp'], ''); ?>
        <?php elseif ($item['type'] === 'Lesson'): ?>
          <img src="<?php echo e(asset_url('/assets/img/logo.png')); ?>" alt="<?php echo e($item['title_display']); ?>" class="thumb-fallback__logo" loading="lazy">
        <?php endif; ?>
      </div>
      <div class="latest-card__body">
        <p class="latest-card__title"><?php echo e($item['title_display'] ?: 'Untitled'); ?></p>
        <p class="latest-card__meta"><?php echo e(format_date($item['item_date'])); ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($popular): ?>
<section class="latest-strip container">
  <h2>Popular content</h2>
  <div class="latest-grid">
    <?php foreach ($popular as $item): ?>
    <a class="latest-card" href="<?php echo e($item['url']); ?>">
      <div class="latest-card__thumb">
        <span class="latest-card__type"><?php echo e($item['type']); ?></span>
        <?php if ($item['thumb']): ?>
          <?php echo render_picture($item['thumb'], $item['thumb_webp'], ''); ?>
        <?php elseif ($item['type'] === 'Lesson'): ?>
          <img src="<?php echo e(asset_url('/assets/img/logo.png')); ?>" alt="<?php echo e($item['title_display']); ?>" class="thumb-fallback__logo" loading="lazy">
        <?php endif; ?>
      </div>
      <div class="latest-card__body">
        <p class="latest-card__title"><?php echo e($item['title_display'] ?: 'Untitled'); ?></p>
        <p class="latest-card__meta"><?php echo e(format_date($item['item_date'])); ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
