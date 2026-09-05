<?php
/**
 * AJAX endpoint used by the WYSIWYG editor's "insert image" toolbar
 * button. Uploads and processes one image, returns its public URL.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request method.'], 405);
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'error' => 'Your session expired. Please reload the page and try again.'], 400);
}

if (empty($_FILES['image'])) {
    json_response(['ok' => false, 'error' => 'No image was uploaded.'], 400);
}

$uploadError = $_FILES['image']['error'];
if ($uploadError !== UPLOAD_ERR_OK) {
    $message = match ($uploadError) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That image is too large for this server to accept.',
        default => 'The image upload failed. Please try again.',
    };
    json_response(['ok' => false, 'error' => $message], 400);
}

try {
    $relPath = process_inline_content_image($_FILES['image']['tmp_name'], $_FILES['image']['name']);
    json_response(['ok' => true, 'url' => upload_url($relPath)]);
} catch (ImageProcessingException $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    app_log('Inline image upload failed: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Something went wrong uploading that image. Please try again.'], 500);
}
