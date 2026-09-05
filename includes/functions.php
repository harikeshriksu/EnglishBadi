<?php
/**
 * General-purpose helper functions used across the whole site.
 * Domain-specific logic lives in its own file: auth.php, sanitizer.php,
 * mailer.php, image.php, quiz.php, seo.php.
 */

// ---------------------------------------------------------------------
// Output / escaping
// ---------------------------------------------------------------------

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---------------------------------------------------------------------
// Error handling / logging
// ---------------------------------------------------------------------

function app_log(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    $dir = PROJECT_ROOT . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function show_friendly_error(string $title, string $message, int $httpCode = 500): never
{
    if (!headers_sent()) {
        http_response_code($httpCode);
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . '</title>';
    echo '<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#F7F8FA;color:#1F2328;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;padding:24px;box-sizing:border-box}';
    echo '.box{max-width:480px;text-align:center;line-height:1.6}h1{color:#4A5FBF;font-size:1.4rem}a{color:#3FAE4B;font-weight:600;text-decoration:none}a:hover{text-decoration:underline}</style></head><body>';
    echo '<div class="box"><h1>' . e($title) . '</h1><p>' . e($message) . '</p><p><a href="' . e(defined('SITE_URL') ? SITE_URL . '/' : '/') . '">Go to the homepage</a></p></div>';
    echo '</body></html>';
    exit;
}

// ---------------------------------------------------------------------
// Settings (cached key/value store backed by the `settings` table)
// ---------------------------------------------------------------------

function all_settings(): array
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            app_log('Failed to load settings: ' . $e->getMessage());
        }
    }

    return $settings;
}

function setting(string $key, string $default = ''): string
{
    $all = all_settings();
    return ($all[$key] ?? '') !== '' ? $all[$key] : $default;
}

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && $token !== '' && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_valid_csrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!csrf_verify($token)) {
        show_friendly_error('Form expired', 'Your form session expired or was submitted twice. Please go back and try again.', 400);
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        show_friendly_error('Page not found', 'That page cannot be opened directly.', 404);
    }
}

// ---------------------------------------------------------------------
// Flash messages (one-time, shown after a redirect)
// ---------------------------------------------------------------------

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------------
// URLs / redirects
// ---------------------------------------------------------------------

function base_url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return rtrim(SITE_URL, '/') . $path;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function current_url(): string
{
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443')) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? (parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost');
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme . '://' . $host . $uri;
}

function youtube_extract_id(string $url): ?string
{
    $patterns = [
        '~youtube\.com/watch\?[^ ]*v=([A-Za-z0-9_-]{6,})~i',
        '~youtube\.com/shorts/([A-Za-z0-9_-]{6,})~i',
        '~youtube\.com/embed/([A-Za-z0-9_-]{6,})~i',
        '~youtu\.be/([A-Za-z0-9_-]{6,})~i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $m)) {
            return $m[1];
        }
    }
    return null;
}

// ---------------------------------------------------------------------
// Slugs
// ---------------------------------------------------------------------

function slugify(string $text): string
{
    $text = trim($text);
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($translit !== false && trim($translit) !== '') {
        $text = $translit;
    }
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    return trim($text, '-');
}

/**
 * Build a slug guaranteed to be unique in $table.slug, appending -2, -3...
 * on collision. $table must always be a hardcoded literal from trusted
 * call sites (never user input) since it is interpolated into SQL.
 */
function unique_slug(string $baseText, string $table, ?int $excludeId = null): string
{
    $slug = slugify($baseText);
    if ($slug === '') {
        $slug = 'item-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    $original = $slug;
    $i = 2;

    while (true) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE slug = ?";
        $params = [$slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $original . '-' . $i;
        $i++;
    }
}

// ---------------------------------------------------------------------
// Categories (shared by lessons / links / posters)
// ---------------------------------------------------------------------

function get_categories(string $type): array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE type = ? ORDER BY display_order, name');
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

function get_category(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ---------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------

function pager_links(int $page, int $totalPages, string $urlPattern): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav class="pager" aria-label="Pagination">';

    if ($page > 1) {
        $html .= '<a class="pager__link" href="' . e(str_replace('{page}', (string) ($page - 1), $urlPattern)) . '">&larr; Previous</a>';
    } else {
        $html .= '<span></span>';
    }

    $html .= '<span class="pager__current">Page ' . (int) $page . ' of ' . (int) $totalPages . '</span>';

    if ($page < $totalPages) {
        $html .= '<a class="pager__link" href="' . e(str_replace('{page}', (string) ($page + 1), $urlPattern)) . '">Next &rarr;</a>';
    } else {
        $html .= '<span></span>';
    }

    $html .= '</nav>';

    return $html;
}

// ---------------------------------------------------------------------
// Text / date helpers
// ---------------------------------------------------------------------

function excerpt_from_html(string $html, int $length = 200): string
{
    // Block-level tags carry no surrounding whitespace of their own (the
    // editor writes "<p>First.</p><p>Second.</p>" with nothing between
    // them), so strip_tags() alone would run words together across
    // paragraph/list/heading boundaries. Turn those boundaries into a
    // space first.
    $html = preg_replace('/<\/(p|div|li|h[1-6]|blockquote|br)\s*>/i', ' ', $html) ?? $html;
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    $truncated = mb_substr($text, 0, $length, 'UTF-8');
    $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > 0) {
        $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
    }
    return $truncated . '...';
}

function format_date(?string $datetime, string $format = 'd M Y'): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

function contains_telugu(string $text): bool
{
    return (bool) preg_match('/[\x{0C00}-\x{0C7F}]/u', $text);
}

/**
 * Render a <picture> with a WEBP source (when available) and a JPEG
 * fallback <img>. $jpgUrl/$webpUrl should already be full site URLs
 * (see base_url()) or absolute external URLs.
 */
function render_picture(string $jpgUrl, ?string $webpUrl, string $alt, string $class = '', bool $lazy = true): string
{
    $loadingAttr = $lazy ? ' loading="lazy"' : '';
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';

    $html = '<picture>';
    if ($webpUrl) {
        $html .= '<source srcset="' . e($webpUrl) . '" type="image/webp">';
    }
    $html .= '<img src="' . e($jpgUrl) . '" alt="' . e($alt) . '"' . $classAttr . $loadingAttr . '>';
    $html .= '</picture>';

    return $html;
}

/** Convert a relative uploads/... path stored in the DB into a full site URL, or null. */
function upload_url(?string $relativePath): ?string
{
    if (!$relativePath) {
        return null;
    }
    return base_url('/' . ltrim($relativePath, '/'));
}
