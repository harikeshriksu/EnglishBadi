<?php
/**
 * SEO helpers: <title>/meta description, canonical, Open Graph, Twitter
 * card, and JSON-LD structured data. Every public page calls
 * render_seo_head() once inside <head>, after setting up an $seo array.
 */

/**
 * @param array{title?:string,description?:string,canonical?:string,type?:string,image?:?string,noindex?:bool} $seo
 */
function render_seo_head(array $seo): void
{
    $siteTitle = setting('site_title', 'English Badi');
    $title = trim($seo['title'] ?? '');
    $fullTitle = $title !== '' ? $title . ' | ' . $siteTitle : $siteTitle . ' | ' . setting('site_tagline', '');
    $description = trim($seo['description'] ?? '') ?: setting('meta_description_default', '');
    $canonical = $seo['canonical'] ?? current_url();
    $type = $seo['type'] ?? 'website';
    $image = $seo['image'] ?? null;
    $noindex = $seo['noindex'] ?? false;

    echo '<title>' . e($fullTitle) . "</title>\n";
    echo '<meta name="description" content="' . e($description) . "\">\n";
    echo '<link rel="canonical" href="' . e($canonical) . "\">\n";
    if ($noindex) {
        echo "<meta name=\"robots\" content=\"noindex,follow\">\n";
    }
    echo '<meta property="og:title" content="' . e($fullTitle) . "\">\n";
    echo '<meta property="og:description" content="' . e($description) . "\">\n";
    echo '<meta property="og:type" content="' . e($type) . "\">\n";
    echo '<meta property="og:url" content="' . e($canonical) . "\">\n";
    echo '<meta property="og:site_name" content="' . e($siteTitle) . "\">\n";

    if ($image) {
        echo '<meta property="og:image" content="' . e($image) . "\">\n";
        echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        echo '<meta name="twitter:image" content="' . e($image) . "\">\n";
    } else {
        echo "<meta name=\"twitter:card\" content=\"summary\">\n";
    }

    echo '<meta name="twitter:title" content="' . e($fullTitle) . "\">\n";
    echo '<meta name="twitter:description" content="' . e($description) . "\">\n";
}

function render_json_ld(array $data): void
{
    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}

function article_json_ld(array $lesson, string $url): array
{
    $data = [
        '@context'      => 'https://schema.org',
        '@type'         => 'Article',
        'headline'      => $lesson['title'],
        'datePublished' => $lesson['publish_date'] ? date('c', strtotime($lesson['publish_date'])) : null,
        'dateModified'  => $lesson['updated_at'] ? date('c', strtotime($lesson['updated_at'])) : null,
        'author'        => ['@type' => 'Organization', 'name' => setting('site_title', 'English Badi')],
        'publisher'     => ['@type' => 'Organization', 'name' => setting('site_title', 'English Badi')],
        'mainEntityOfPage' => $url,
        'description'   => $lesson['meta_description'] ?: excerpt_from_html($lesson['body'], 160),
    ];

    if (!empty($lesson['featured_image'])) {
        $data['image'] = base_url('/' . $lesson['featured_image']);
    }

    return array_filter($data, static fn ($v) => $v !== null);
}

function quiz_json_ld(array $quiz, array $questions, string $url): array
{
    return [
        '@context'    => 'https://schema.org',
        '@type'       => 'Quiz',
        'name'        => $quiz['title'],
        'description' => $quiz['description'] ?: ('A practice quiz on ' . $quiz['topic']),
        'url'         => $url,
        'about'       => ['@type' => 'Thing', 'name' => $quiz['topic'] ?: 'English practice'],
        'educationalAlignment' => [
            '@type'            => 'AlignmentObject',
            'alignmentType'    => 'educationalSubject',
            'targetName'       => 'English as a Second Language',
        ],
        'numberOfQuestions' => count($questions),
    ];
}
