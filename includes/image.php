<?php
/**
 * Image upload processing: format detection, orientation, square/contain
 * resizing, JPEG + WEBP output. Uses Imagick when available (it supports
 * more formats: HEIC/HEIF, TIFF, first-page-of-PDF) and falls back to GD
 * otherwise, since a Hostinger shared-hosting account may only have one
 * of the two extensions enabled.
 *
 * Every public function here either returns a result array or throws
 * ImageProcessingException with a message that is already safe and
 * friendly to show directly to the (non-technical) admin user.
 */

class ImageProcessingException extends RuntimeException
{
}

const ALLOWED_IMAGE_MIMES = [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp',
    'image/tiff', 'image/heic', 'image/heif', 'image/avif', 'application/pdf',
];

function detect_real_mime(string $path): string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    return $mime ?: 'application/octet-stream';
}

function validate_image_mime_or_throw(string $mime): void
{
    if (!in_array($mime, ALLOWED_IMAGE_MIMES, true)) {
        throw new ImageProcessingException(
            "That file type isn't supported. Please upload a JPG, PNG, WEBP, GIF, BMP, TIFF, HEIC or PDF file."
        );
    }
}

function build_safe_basename(string $originalName): string
{
    $slug = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($slug === '') {
        $slug = 'image';
    }
    return $slug . '-' . time() . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
}

// ---------------------------------------------------------------------
// Low-level backend-aware primitives. An "image handle" is always
// ['type' => 'imagick'|'gd', 'handle' => Imagick|GdImage].
// ---------------------------------------------------------------------

function img_open(string $path, string $mime): array
{
    if (class_exists('Imagick')) {
        try {
            $im = new Imagick();
            $im->readImage($path . (str_contains($mime, 'pdf') ? '[0]' : ''));
            $im->setIteratorIndex(0);
            return ['type' => 'imagick', 'handle' => $im];
        } catch (Throwable $e) {
            // fall through and try GD instead
        }
    }

    if (extension_loaded('gd')) {
        $gd = gd_create_from_mime($path, $mime);
        if ($gd !== null) {
            return ['type' => 'gd', 'handle' => $gd];
        }
    }

    throw new ImageProcessingException(
        "This file couldn't be processed on this server (its format needs a library that isn't installed). " .
        "Please re-save it as a JPG or PNG file and upload it again."
    );
}

function gd_create_from_mime(string $path, string $mime)
{
    switch ($mime) {
        case 'image/jpeg':
            return (@imagecreatefromjpeg($path)) ?: null;
        case 'image/png':
            $im = @imagecreatefrompng($path);
            if ($im) {
                imagepalettetotruecolor($im);
                imagealphablending($im, true);
                imagesavealpha($im, true);
            }
            return $im ?: null;
        case 'image/gif':
            return (@imagecreatefromgif($path)) ?: null;
        case 'image/webp':
            return function_exists('imagecreatefromwebp') ? ((@imagecreatefromwebp($path)) ?: null) : null;
        case 'image/bmp':
            return function_exists('imagecreatefrombmp') ? ((@imagecreatefrombmp($path)) ?: null) : null;
        case 'image/avif':
            return function_exists('imagecreatefromavif') ? ((@imagecreatefromavif($path)) ?: null) : null;
        default:
            // TIFF / HEIC / HEIF / PDF are not supported by GD.
            return null;
    }
}

function img_dimensions(array $img): array
{
    if ($img['type'] === 'imagick') {
        return [$img['handle']->getImageWidth(), $img['handle']->getImageHeight()];
    }
    return [imagesx($img['handle']), imagesy($img['handle'])];
}

function img_clone(array $img): array
{
    if ($img['type'] === 'imagick') {
        return ['type' => 'imagick', 'handle' => clone $img['handle']];
    }

    [$w, $h] = img_dimensions($img);
    $copy = imagecreatetruecolor($w, $h);
    imagealphablending($copy, false);
    imagesavealpha($copy, true);
    imagecopy($copy, $img['handle'], 0, 0, 0, 0, $w, $h);

    return ['type' => 'gd', 'handle' => $copy];
}

function img_destroy(array $img): void
{
    if ($img['type'] === 'imagick') {
        $img['handle']->clear();
        $img['handle']->destroy();
        return;
    }
    imagedestroy($img['handle']);
}

function img_auto_orient(array &$img, string $path, string $mime): void
{
    if ($img['type'] === 'imagick') {
        try {
            $img['handle']->autoOrientImage();
        } catch (Throwable $e) {
            // not critical - continue with the image as-is
        }
        return;
    }

    if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
        return;
    }

    $exif = @exif_read_data($path);
    $orientation = (int) ($exif['Orientation'] ?? 1);
    if ($orientation <= 1) {
        return;
    }

    $gd = $img['handle'];
    $rotated = match ($orientation) {
        3 => imagerotate($gd, 180, 0),
        6 => imagerotate($gd, -90, 0),
        8 => imagerotate($gd, 90, 0),
        default => $gd,
    };

    if ($rotated !== $gd) {
        imagedestroy($gd);
        $img['handle'] = $rotated;
    }
}

/** Scale down only if larger than $maxDim on its longest side; preserves aspect ratio. */
function img_resize_contain(array &$img, int $maxDim): void
{
    [$w, $h] = img_dimensions($img);
    if ($w <= $maxDim && $h <= $maxDim) {
        return;
    }
    $ratio = min($maxDim / $w, $maxDim / $h);
    img_resize_exact($img, max(1, (int) round($w * $ratio)), max(1, (int) round($h * $ratio)));
}

function img_resize_exact(array &$img, int $w, int $h): void
{
    [$curW, $curH] = img_dimensions($img);
    if ($curW === $w && $curH === $h) {
        return;
    }

    if ($img['type'] === 'imagick') {
        $img['handle']->resizeImage($w, $h, Imagick::FILTER_LANCZOS, 1);
        return;
    }

    $src = $img['handle'];
    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $curW, $curH);
    imagedestroy($src);
    $img['handle'] = $dst;
}

function img_crop_rect(array &$img, int $x, int $y, int $w, int $h): void
{
    if ($img['type'] === 'imagick') {
        $img['handle']->cropImage($w, $h, $x, $y);
        $img['handle']->setImagePage(0, 0, 0, 0);
        return;
    }

    $src = $img['handle'];
    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, $x, $y, $w, $h, $w, $h);
    imagedestroy($src);
    $img['handle'] = $dst;
}

/** Pad (letterbox) onto a near-white square canvas without cropping any content. */
function img_letterbox_square(array &$img, int $outSize): void
{
    [$w, $h] = img_dimensions($img);
    $scale = min($outSize / $w, $outSize / $h);
    $newW = max(1, (int) round($w * $scale));
    $newH = max(1, (int) round($h * $scale));
    img_resize_exact($img, $newW, $newH);

    $offsetX = (int) (($outSize - $newW) / 2);
    $offsetY = (int) (($outSize - $newH) / 2);

    if ($img['type'] === 'imagick') {
        $canvas = new Imagick();
        $canvas->newImage($outSize, $outSize, new ImagickPixel('#F7F8FA'));
        $canvas->setImageFormat($img['handle']->getImageFormat() ?: 'jpeg');
        $canvas->compositeImage($img['handle'], Imagick::COMPOSITE_OVER, $offsetX, $offsetY);
        $img['handle']->clear();
        $img['handle']->destroy();
        $img['handle'] = $canvas;
        return;
    }

    $canvas = imagecreatetruecolor($outSize, $outSize);
    $bg = imagecolorallocate($canvas, 0xF7, 0xF8, 0xFA);
    imagefill($canvas, 0, 0, $bg);
    imagecopy($canvas, $img['handle'], $offsetX, $offsetY, 0, 0, $newW, $newH);
    imagedestroy($img['handle']);
    $img['handle'] = $canvas;
}

/**
 * Produce an exact $outSize x $outSize square.
 *  - $cropBox (['x','y','size'] in source pixel coordinates), when given,
 *    is used verbatim (from the admin's manual crop tool).
 *  - otherwise, if the image is already square, it is simply resized.
 *  - otherwise, if $letterbox is true ("keep full image" was chosen),
 *    the whole image is padded onto a near-white square.
 *  - otherwise, a centred square crop is taken automatically.
 */
function img_crop_square(array &$img, ?array $cropBox, int $outSize, bool $letterbox): void
{
    [$w, $h] = img_dimensions($img);

    if ($cropBox !== null) {
        $size = (int) min($cropBox['size'], $w, $h);
        $size = max(1, $size);
        $x = max(0, min((int) $cropBox['x'], $w - $size));
        $y = max(0, min((int) $cropBox['y'], $h - $size));
        img_crop_rect($img, $x, $y, $size, $size);
        img_resize_exact($img, $outSize, $outSize);
        return;
    }

    if ($w === $h) {
        img_resize_exact($img, $outSize, $outSize);
        return;
    }

    if ($letterbox) {
        img_letterbox_square($img, $outSize);
        return;
    }

    $size = min($w, $h);
    $x = (int) (($w - $size) / 2);
    $y = (int) (($h - $size) / 2);
    img_crop_rect($img, $x, $y, $size, $size);
    img_resize_exact($img, $outSize, $outSize);
}

function img_save_jpeg(array $img, string $destPath, int $quality = 85): void
{
    ensure_dir_for(dirname($destPath));

    if ($img['type'] === 'imagick') {
        $clone = clone $img['handle'];
        $clone->setImageFormat('jpeg');
        $clone->setImageCompressionQuality($quality);
        $clone->setImageBackgroundColor(new ImagickPixel('#ffffff'));
        $clone = $clone->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $clone->writeImage($destPath);
        $clone->clear();
        $clone->destroy();
        return;
    }

    $src = $img['handle'];
    $w = imagesx($src);
    $h = imagesy($src);
    $flat = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($flat, 255, 255, 255);
    imagefill($flat, 0, 0, $white);
    imagecopy($flat, $src, 0, 0, 0, 0, $w, $h);
    imagejpeg($flat, $destPath, $quality);
    imagedestroy($flat);
}

/** Returns the destination path on success, or null if WEBP isn't supported on this server. */
function img_save_webp(array $img, string $destPath, int $quality = 85): ?string
{
    ensure_dir_for(dirname($destPath));

    if ($img['type'] === 'imagick') {
        try {
            $clone = clone $img['handle'];
            $clone->setImageFormat('webp');
            $clone->setImageCompressionQuality($quality);
            $clone->writeImage($destPath);
            $clone->clear();
            $clone->destroy();
            return $destPath;
        } catch (Throwable $e) {
            return null;
        }
    }

    if (!function_exists('imagewebp')) {
        return null;
    }
    $ok = @imagewebp($img['handle'], $destPath, $quality);
    return $ok ? $destPath : null;
}

function ensure_dir_for(string $dir): void
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ---------------------------------------------------------------------
// High-level entry points used by the admin forms
// ---------------------------------------------------------------------

/**
 * @param array{x:int,y:int,size:int}|null $cropBox source-pixel square crop, or null
 */
function process_poster_image(string $tmpPath, string $originalName, ?array $cropBox = null, bool $keepFullNoCrop = false): array
{
    $mime = detect_real_mime($tmpPath);
    validate_image_mime_or_throw($mime);
    $img = img_open($tmpPath, $mime);
    img_auto_orient($img, $tmpPath, $mime);

    $base = build_safe_basename($originalName);
    $paths = [
        'image_path'      => 'uploads/posters/' . $base . '.jpg',
        'webp_path'       => 'uploads/posters/' . $base . '.webp',
        'thumb_path'      => 'uploads/posters/thumbs/' . $base . '.jpg',
        'webp_thumb_path' => 'uploads/posters/thumbs/' . $base . '.webp',
    ];

    img_crop_square($img, $cropBox, 1080, $keepFullNoCrop);
    img_save_jpeg($img, PROJECT_ROOT . '/' . $paths['image_path'], 85);
    if (img_save_webp($img, PROJECT_ROOT . '/' . $paths['webp_path'], 85) === null) {
        $paths['webp_path'] = null;
    }

    img_resize_exact($img, 400, 400);
    img_save_jpeg($img, PROJECT_ROOT . '/' . $paths['thumb_path'], 85);
    if (img_save_webp($img, PROJECT_ROOT . '/' . $paths['webp_thumb_path'], 85) === null) {
        $paths['webp_thumb_path'] = null;
    }

    img_destroy($img);

    return $paths;
}

function process_lesson_image(string $tmpPath, string $originalName): array
{
    $mime = detect_real_mime($tmpPath);
    validate_image_mime_or_throw($mime);
    $original = img_open($tmpPath, $mime);
    img_auto_orient($original, $tmpPath, $mime);

    $base = build_safe_basename($originalName);
    $paths = [
        'image_path'      => 'uploads/lessons/' . $base . '.jpg',
        'webp_path'       => 'uploads/lessons/' . $base . '.webp',
        'thumb_path'      => 'uploads/lessons/thumbs/' . $base . '.jpg',
        'webp_thumb_path' => 'uploads/lessons/thumbs/' . $base . '.webp',
    ];

    $thumb = img_clone($original);
    img_crop_square($thumb, null, 300, false);
    img_save_jpeg($thumb, PROJECT_ROOT . '/' . $paths['thumb_path'], 85);
    if (img_save_webp($thumb, PROJECT_ROOT . '/' . $paths['webp_thumb_path'], 85) === null) {
        $paths['webp_thumb_path'] = null;
    }
    img_destroy($thumb);

    img_resize_contain($original, 1200);
    img_save_jpeg($original, PROJECT_ROOT . '/' . $paths['image_path'], 85);
    if (img_save_webp($original, PROJECT_ROOT . '/' . $paths['webp_path'], 85) === null) {
        $paths['webp_path'] = null;
    }
    img_destroy($original);

    return $paths;
}

function process_link_thumbnail(string $tmpPath, string $originalName): array
{
    $mime = detect_real_mime($tmpPath);
    validate_image_mime_or_throw($mime);
    $original = img_open($tmpPath, $mime);
    img_auto_orient($original, $tmpPath, $mime);

    $base = build_safe_basename($originalName);
    $paths = [
        'image_path' => 'uploads/links/' . $base . '.jpg',
        'webp_path'  => 'uploads/links/' . $base . '.webp',
    ];

    img_crop_square($original, null, 300, false);
    img_save_jpeg($original, PROJECT_ROOT . '/' . $paths['image_path'], 85);
    if (img_save_webp($original, PROJECT_ROOT . '/' . $paths['webp_path'], 85) === null) {
        $paths['webp_path'] = null;
    }
    img_destroy($original);

    return $paths;
}

/** Used by the lesson-body editor's "insert image inline" toolbar button. Returns a single relative path. */
function process_inline_content_image(string $tmpPath, string $originalName): string
{
    $mime = detect_real_mime($tmpPath);
    validate_image_mime_or_throw($mime);
    $img = img_open($tmpPath, $mime);
    img_auto_orient($img, $tmpPath, $mime);
    img_resize_contain($img, 1600);

    $base = build_safe_basename($originalName);
    $rel = 'uploads/lessons/content-' . $base . '.jpg';
    img_save_jpeg($img, PROJECT_ROOT . '/' . $rel, 85);
    img_destroy($img);

    return $rel;
}

/** Deletes an uploaded image and its known thumb/webp siblings, ignoring any that don't exist. */
function delete_image_files(array $relativePaths): void
{
    foreach ($relativePaths as $rel) {
        if (!$rel) {
            continue;
        }
        $full = PROJECT_ROOT . '/' . ltrim($rel, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
