<?php
declare(strict_types=1);

const ORDER_STATUSES = ['pending' => 'Awaiting payment', 'paid' => 'Paid, to pack', 'packed' => 'Packed', 'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

function admin_view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $me = staff();
    ob_start();
    require __DIR__ . "/views/$name.php";
    $content = ob_get_clean();
    require __DIR__ . '/views/layout.php';
}

/** "R1 234,50", "120.5", "120" → cents. Null when empty or not a number. */
function parse_rand(?string $s): ?int
{
    $s = str_replace([' ', 'R', 'r', "\u{00A0}"], '', trim((string) $s));
    if ($s === '') {
        return null;
    }
    $s = str_replace(',', '.', $s);
    return is_numeric($s) && (float) $s >= 0 ? (int) round((float) $s * 100) : null;
}

function rand_input(?int $cents): string
{
    return $cents === null ? '' : number_format($cents / 100, 2, '.', '');
}

/** Keep simple formatting only (same rules as the import). */
function clean_html(string $html): string
{
    $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html);
    $html = strip_tags($html, '<p><br><ul><ol><li><strong><em><b><i>');
    return trim(preg_replace('#<(\w+)\s[^>]*>#', '<$1>', $html));
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s), '-'));
    return $s ?: 'item';
}

function unique_slug(string $table, string $slug, ?int $exceptId = null): string
{
    $base = $slug;
    for ($i = 2; q("SELECT 1 FROM $table WHERE slug = ? AND id IS NOT ?", [$slug, $exceptId])->fetchColumn(); $i++) {
        $slug = "$base-$i";
    }
    return $slug;
}

/**
 * Save an uploaded photo: checked by its real bytes (not the name or browser's type), kept as an original
 * outside the web root, published as WebP. Returns [path, width, height, original] or an error string.
 */
function save_upload(array $file, string $pathBase): array|string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE ? 'That photo is too large (max 15 MB).' : 'Upload failed, please try again.';
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        return 'That photo is too large (max 15 MB).';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !@getimagesize($file['tmp_name'])) {
        return 'Please upload a JPG, PNG or WebP photo.';
    }
    $original = 'up-' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], STORAGE . '/originals/' . $original)) {
        return 'Could not save the photo.';
    }
    $path = $pathBase . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    [$w, $h] = build_webp(STORAGE . '/originals/' . $original, $path);
    return [$path, $w, $h, $original];
}

function delete_webp(string $path): void
{
    foreach (IMAGE_WIDTHS as $w) {
        @unlink(PUBLIC_DIR . "/media/$path-$w.webp");
    }
}

/** Normalise $_FILES['x'] with multiple files into a list of single-file arrays. */
function uploaded_list(string $field): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f || !is_array($f['name'])) {
        return $f && $f['error'] !== UPLOAD_ERR_NO_FILE ? [$f] : [];
    }
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if ($f['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
    }
    return $out;
}

/** Put stock back for a cancelled paid order (the reverse of deduct_stock). */
function restock(int $orderId): void
{
    foreach (q('SELECT product_id, qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL', [$orderId]) as $it) {
        $parts = q('SELECT product_id, qty FROM bundle_items WHERE bundle_id = ?', [$it['product_id']])->fetchAll()
            ?: [['product_id' => $it['product_id'], 'qty' => 1]];
        foreach ($parts as $part) {
            q('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ? AND stock_qty IS NOT NULL', [$it['qty'] * $part['qty'], $part['product_id']]);
        }
    }
}
