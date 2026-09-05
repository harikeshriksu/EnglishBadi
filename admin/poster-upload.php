<?php
/**
 * AJAX endpoint behind the poster drag-and-drop uploader. Accepts one or
 * more images plus a parallel crop decision per file (from the client-side
 * crop tool) and processes each through the same pipeline a single upload
 * would use.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/includes/admin-guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request method.'], 405);
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'error' => 'Your session expired. Please reload the page and try again.'], 400);
}

if (empty($_FILES['images']['name'][0])) {
    json_response(['ok' => false, 'error' => 'No images were uploaded.'], 400);
}

$modes = $_POST['modes'] ?? [];
$xs = $_POST['x'] ?? [];
$ys = $_POST['y'] ?? [];
$sizes = $_POST['size'] ?? [];

$count = count($_FILES['images']['name']);

$stmt = db()->query('SELECT COALESCE(MAX(display_order), 0) FROM posters');
$displayOrder = (int) $stmt->fetchColumn();

$inserted = 0;
$failures = [];

for ($i = 0; $i < $count; $i++) {
    $originalName = $_FILES['images']['name'][$i] ?? ('file ' . ($i + 1));

    if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $failures[] = $originalName . ': upload failed.';
        continue;
    }

    $mode = $modes[$i] ?? 'keep';
    $cropBox = null;
    $keepFull = true;

    if ($mode === 'crop') {
        $size = (int) ($sizes[$i] ?? 0);
        if ($size > 0) {
            $cropBox = ['x' => (int) ($xs[$i] ?? 0), 'y' => (int) ($ys[$i] ?? 0), 'size' => $size];
            $keepFull = false;
        }
    }

    try {
        $paths = process_poster_image($_FILES['images']['tmp_name'][$i], $originalName, $cropBox, $keepFull);

        $displayOrder++;
        $stmt = db()->prepare(
            'INSERT INTO posters (caption, image_path, thumb_path, webp_path, webp_thumb_path, alt_text, category_id, display_order) VALUES (NULL, ?, ?, ?, ?, NULL, NULL, ?)'
        );
        $stmt->execute([$paths['image_path'], $paths['thumb_path'], $paths['webp_path'], $paths['webp_thumb_path'], $displayOrder]);
        $inserted++;
    } catch (ImageProcessingException $e) {
        $failures[] = $originalName . ': ' . $e->getMessage();
    } catch (Throwable $e) {
        app_log('Poster upload failed for ' . $originalName . ': ' . $e->getMessage());
        $failures[] = $originalName . ': something went wrong processing this image.';
    }
}

if ($inserted === 0) {
    json_response(['ok' => false, 'error' => $failures ? implode(' ', $failures) : 'No images could be uploaded.']);
}

json_response(['ok' => true, 'inserted' => $inserted, 'failures' => $failures]);
