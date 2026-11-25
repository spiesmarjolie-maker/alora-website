<?php
declare(strict_types=1);
/**
 * Publish the cleaned-up photos from tools/photos.py:
 *   storage/enhanced/shop/{stem}.jpg   → public/media/{path}-{600,1200,1600}.webp     (shop, ivory backdrop)
 *   storage/enhanced/cutout/{stem}.png → public/media/{path}-cut-{600,1200,1600}.webp (film, transparent)
 * Originals stay untouched; re-running tools/import-wp.php is NOT needed.
 *   php -c dev/php.ini tools/apply-photos.php
 */
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/images.php';

$shop = $cut = 0;
foreach (q('SELECT id, original, path FROM product_images') as $im) {
    $stem = pathinfo($im['original'], PATHINFO_FILENAME);
    // Approved ChatGPT "professional camera" photos win; files in chatgpt/rejected/ are never used.
    $gpt = STORAGE . "/enhanced/chatgpt/$stem.png";
    $s = is_file($gpt) ? $gpt : STORAGE . "/enhanced/shop/$stem.jpg";
    $gptCut = STORAGE . "/enhanced/chatgpt/$stem-cut.png";
    $c = is_file($gptCut) ? $gptCut : STORAGE . "/enhanced/cutout/$stem.png";
    if (is_file($s)) {
        foreach (IMAGE_WIDTHS as $w) {
            @unlink(PUBLIC_DIR . "/media/{$im['path']}-$w.webp");   // force a rebuild from the new photo
        }
        [$w, $h] = build_webp($s, $im['path']);
        q('UPDATE product_images SET width = ?, height = ? WHERE id = ?', [$w, $h, $im['id']]);
        $shop++;
    }
    foreach (IMAGE_WIDTHS as $w) {
        @unlink(PUBLIC_DIR . "/media/{$im['path']}-cut-$w.webp");
    }
    if (is_file($c)) {
        build_webp($c, $im['path'] . '-cut');
        $cut++;
    }
}
echo "Published $shop shop photos and $cut film cut-outs.\n";
