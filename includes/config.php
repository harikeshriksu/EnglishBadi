<?php
/**
 * Application bootstrap.
 *
 * Every public page starts with:
 *   require_once __DIR__ . '/includes/config.php';
 * and every admin page starts with:
 *   require_once __DIR__ . '/../includes/config.php';
 *   require_once __DIR__ . '/includes/admin-guard.php';
 *
 * This one file pulls in configuration, the database connection, and
 * every helper function used across the site, in the right order, so
 * no other file needs its own chain of requires.
 */

define('PROJECT_ROOT', dirname(__DIR__));

/**
 * config.php is looked for in two places, in this order:
 *   1. One directory ABOVE the web root (outside public_html) - the
 *      recommended location, since a Git-based deploy that syncs
 *      public_html can never reach a file that lives above it.
 *   2. Inside the web root itself, next to this project's other files
 *      (the simpler option, kept for anyone who hasn't moved it yet).
 * See README.md for how to set either one up.
 */
$externalConfigFile = dirname(PROJECT_ROOT) . '/config.php';
$inRootConfigFile = PROJECT_ROOT . '/config.php';
$configFile = file_exists($externalConfigFile) ? $externalConfigFile : $inRootConfigFile;

// Written once config.php has ever loaded successfully, next to wherever
// config.php itself is recommended to live (outside public_html) so it
// survives the same deploys config.php now does. Lets us tell "this has
// never been set up" apart from "this was working and config.php just
// went missing" - the second one shouldn't show a public setup wizard.
$setupMarkerFile = dirname(PROJECT_ROOT) . '/.eb-configured';

if (!file_exists($configFile)) {
    http_response_code(500);

    if (file_exists($setupMarkerFile)) {
        ?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>English Badi</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;max-width:600px;margin:60px auto;padding:0 20px;color:#1F2328;line-height:1.6;">
<h1 style="color:#4A5FBF;">We're having a technical problem</h1>
<p>Please try again in a few minutes. If this keeps happening, please let the site administrator know.</p>
</body>
</html>
        <?php
        exit;
    }

    ?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>One more step - English Badi</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;max-width:600px;margin:60px auto;padding:0 20px;color:#1F2328;line-height:1.6;">
<h1 style="color:#4A5FBF;">One more step</h1>
<p><code>config.php</code> was not found.</p>
<p>Copy <code>config.php.example</code>, rename the copy to <code>config.php</code>, fill in your database details, and either place it one directory above your site's root folder (recommended) or in the root folder itself, then reload this page.</p>
<p>Full instructions are in <code>README.md</code>.</p>
</body>
</html>
    <?php
    exit;
}

require_once $configFile;

if (!file_exists($setupMarkerFile)) {
    @touch($setupMarkerFile);
}

// ---- Error handling: log everything, never show raw errors ----
error_reporting(E_ALL);
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');
ini_set('display_startup_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');
ini_set('log_errors', '1');

if (!is_dir(PROJECT_ROOT . '/logs')) {
    @mkdir(PROJECT_ROOT . '/logs', 0755, true);
}
ini_set('error_log', PROJECT_ROOT . '/logs/php-error.log');

date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Asia/Kolkata');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/sanitizer.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/image.php';
require_once __DIR__ . '/quiz.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/icons.php';

set_exception_handler(function (Throwable $e): void {
    app_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    show_friendly_error(
        'Something went wrong',
        'Please try again in a few minutes. If this keeps happening, please let the site administrator know.'
    );
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    app_log("PHP error [$severity] $message in $file:$line");
    return true;
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        app_log('Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        if (!headers_sent() && !(defined('DEBUG_MODE') && DEBUG_MODE)) {
            show_friendly_error('Something went wrong', 'Please try again in a few minutes.');
        }
    }
});

// ---- Session hardening ----
if (session_status() === PHP_SESSION_NONE) {
    $httpsOn = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $httpsOn,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name('ebsess');
    session_start();
}
