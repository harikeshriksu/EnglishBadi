<?php
/** Dynamic XML sitemap, addressed as /sitemap.xml via the root .htaccess rewrite. */
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [];
$urls[] = ['loc' => base_url('/'), 'priority' => '1.0'];

foreach (['start-here', 'lessons', 'links', 'posters', 'quizzes', 'about', 'contact'] as $path) {
    $urls[] = ['loc' => base_url('/' . $path), 'priority' => '0.8'];
}

$lessons = db()->query("SELECT slug, updated_at FROM lessons WHERE status = 'published'")->fetchAll();
foreach ($lessons as $l) {
    $urls[] = [
        'loc'     => base_url('/lessons/' . $l['slug']),
        'lastmod' => date('c', strtotime((string) $l['updated_at'])),
        'priority' => '0.7',
    ];
}

$quizzes = db()->query("SELECT slug, updated_at FROM quizzes WHERE status = 'published'")->fetchAll();
foreach ($quizzes as $q) {
    $urls[] = [
        'loc'     => base_url('/quiz/' . $q['slug']),
        'lastmod' => date('c', strtotime((string) $q['updated_at'])),
        'priority' => '0.7',
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . e($u['loc']) . "</loc>\n";
    if (!empty($u['lastmod'])) {
        echo '    <lastmod>' . e($u['lastmod']) . "</lastmod>\n";
    }
    echo '    <priority>' . e($u['priority']) . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
