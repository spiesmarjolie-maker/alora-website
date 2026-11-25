<?php
declare(strict_types=1);

/** Widths we publish. 600 for cards and grids, 1200 for product pages, 1600 for large hero use. */
const IMAGE_WIDTHS = [600, 1200, 1600];

/**
 * Make WebP copies of an original photo at each width: public/media/{path}-{width}.webp
 * Fixes phone rotation (EXIF) and never upscales. Returns [width, height] of the original.
 */
function build_webp(string $source, string $path): array
{
    $data = file_get_contents($source);
    $img = @imagecreatefromstring($data);
    if (!$img) {
        throw new RuntimeException("Not an image: $source");
    }
    // Phone photos are often stored sideways with an EXIF "rotate me" flag.
    if (function_exists('exif_read_data') && str_starts_with($data, "\xFF\xD8")) {
        $o = @exif_read_data($source)['Orientation'] ?? 1;
        $img = match ($o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
    }
    imagepalettetotruecolor($img);
    imagealphablending($img, true);
    imagesavealpha($img, true);
    $w = imagesx($img);
    $h = imagesy($img);

    $base = PUBLIC_DIR . '/media/' . $path;
    @mkdir(dirname($base), 0775, true);
    foreach (IMAGE_WIDTHS as $target) {
        $out = "$base-$target.webp";
        if (is_file($out) && filemtime($out) >= filemtime($source)) {
            continue;
        }
        $tw = min($target, $w);
        $th = (int) round($h * $tw / $w);
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagewebp($dst, $out, 80);
    }
    return [$w, $h];
}
