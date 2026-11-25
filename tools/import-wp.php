<?php
declare(strict_types=1);
/**
 * One-off import from the old WordPress/WooCommerce shop (public Store API, read-only).
 * Safe to re-run: rows are matched on their WordPress id and photos already downloaded are skipped.
 *
 *   php -c dev/php.ini tools/import-wp.php
 */
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/images.php';

const WP = 'https://joliefemme.co.za';

function wp_get(string $path): array
{
    $c = curl_init(WP . $path);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Alora-migration/1.0',
        CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA,
    ]);
    $body = curl_exec($c);
    $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
    if ($body === false || $code !== 200) {
        throw new RuntimeException("GET $path failed: HTTP $code " . curl_error($c));
    }
    return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
}

function download(string $url, string $to): void
{
    if (is_file($to) && filesize($to) > 0) {
        return;
    }
    $fh = fopen($to . '.part', 'wb');
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_FILE => $fh,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'Alora-migration/1.0',
        CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA,
    ]);
    $ok = curl_exec($c);
    $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
    fclose($fh);
    if (!$ok || $code !== 200) {
        @unlink($to . '.part');
        throw new RuntimeException("download failed ($code): $url");
    }
    rename($to . '.part', $to);
}

function plain(string $html): string
{
    return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/** Keep simple formatting only; everything else is stripped. */
function clean_html(string $html): string
{
    $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $html);
    $html = strip_tags($html, '<p><br><ul><ol><li><strong><em><b><i>');
    return trim(preg_replace('#<(\w+)\s[^>]*>#', '<$1>', $html)); // drop all attributes
}

$orig = STORAGE . '/originals';
@mkdir($orig, 0775, true);
$pdo = db();

// ---- Categories -------------------------------------------------------------
$cats = wp_get('/wp-json/wc/store/v1/products/categories?per_page=100');
$catId = [];
$sort = 0;
foreach ($cats as $c) {
    $name = plain($c['name']);
    $name = str_ireplace('Stationary', 'Stationery', $name); // fix the old spelling (slug stays the same)
    q('INSERT INTO categories (wp_id, slug, name, description, sort) VALUES (?,?,?,?,?)
       ON CONFLICT(wp_id) DO UPDATE SET slug=excluded.slug, name=excluded.name, description=excluded.description',
        [$c['id'], $c['slug'], $name, plain($c['description'] ?? ''), $sort++]);
    $catId[$c['id']] = (int) q('SELECT id FROM categories WHERE wp_id = ?', [$c['id']])->fetchColumn();

    if (!empty($c['image']['src'])) {
        $ext = strtolower(pathinfo(parse_url($c['image']['src'], PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
        $file = 'cat-' . $c['image']['id'] . '.' . $ext;
        download($c['image']['src'], "$orig/$file");
        $path = 'c/' . $c['slug'];
        build_webp("$orig/$file", $path);
        q('UPDATE categories SET image = ? WHERE id = ?', [$path, $catId[$c['id']]]);
    }
}
echo count($cats), " categories\n";

// ---- Products ---------------------------------------------------------------
$products = [];
for ($page = 1; ; $page++) {
    $batch = wp_get("/wp-json/wc/store/v1/products?per_page=100&page=$page");
    $products = array_merge($products, $batch);
    if (count($batch) < 100) {
        break;
    }
}

// The API lists newest first; insert oldest first so a higher id always means newer.
$products = array_reverse($products);
$images = 0;
foreach ($products as $i => $p) {
    $pr = $p['prices'];
    $price = (int) $pr['price'];
    $regular = (int) ($pr['regular_price'] ?: $pr['price']);

    $text = $p['stock_availability']['text'] ?? '';
    $stock = !$p['is_in_stock'] ? 0 : (preg_match('/(\d+)\s+in stock/i', $text, $m) ? (int) $m[1] : null);

    q('INSERT INTO products (wp_id, slug, name, sku, short_desc, description, price_cents, regular_cents, stock_qty, active)
       VALUES (?,?,?,?,?,?,?,?,?,1)
       ON CONFLICT(wp_id) DO UPDATE SET slug=excluded.slug, name=excluded.name, sku=excluded.sku,
         short_desc=excluded.short_desc, description=excluded.description, price_cents=excluded.price_cents,
         regular_cents=excluded.regular_cents, stock_qty=excluded.stock_qty, updated_at=datetime(\'now\')',
        [$p['id'], $p['slug'], plain($p['name']), $p['sku'] ?: null, clean_html($p['short_description']),
         clean_html($p['description']), $price, $regular, $stock]);
    $pid = (int) q('SELECT id FROM products WHERE wp_id = ?', [$p['id']])->fetchColumn();

    q('DELETE FROM product_categories WHERE product_id = ?', [$pid]);
    foreach ($p['categories'] as $c) {
        if (isset($catId[$c['id']])) {
            q('INSERT OR IGNORE INTO product_categories VALUES (?, ?)', [$pid, $catId[$c['id']]]);
        }
    }

    q('DELETE FROM product_images WHERE product_id = ?', [$pid]);
    foreach ($p['images'] as $n => $img) {
        $ext = strtolower(pathinfo(parse_url($img['src'], PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
        $file = $img['id'] . '.' . $ext;
        download($img['src'], "$orig/$file");
        $path = 'p/' . $p['slug'] . ($n ? '-' . ($n + 1) : '');
        [$w, $h] = build_webp("$orig/$file", $path);
        q('INSERT INTO product_images (product_id, wp_media_id, original, path, alt, width, height, sort) VALUES (?,?,?,?,?,?,?,?)',
            [$pid, $img['id'], $file, $path, plain($img['alt'] ?: $p['name']), $w, $h, $n]);
        $images++;
    }
    echo sprintf("\r%3d/%d products, %d photos", $i + 1, count($products), $images);
}
echo "\n";

// ---- Shipping, settings, redirects (only set if missing) ---------------------
if (!q('SELECT COUNT(*) FROM shipping_methods')->fetchColumn()) {
    q("INSERT INTO shipping_methods (name, description, price_cents, needs_address, sort) VALUES
       ('PUDO locker pickup', 'Collect from a PUDO locker near you', 10000, 0, 1),
       ('PUDO door to door', 'Delivered to your door by PUDO', 12000, 1, 2)");
}
foreach ([
    'shop_name' => 'Alora',
    'tagline' => 'Where dreams meet light',
    'phone' => '081 737 1752',
    'whatsapp' => '27817371752',
    'currency' => 'ZAR',
    'order_prefix' => 'AL-',
    'next_order_no' => '10001',
] as $k => $v) {
    if (setting($k) === null) {
        set_setting($k, $v);
    }
}
foreach ([
    '/786-2/' => '/shop/',
    '/home/' => '/',
    '/home-2/' => '/',
    '/elementor-2163/' => '/personalised/',
    '/terms-conditions/' => '/terms/',
    '/my-account/' => '/account/',
    '/cart/' => '/bag/',
] as $old => $new) {
    q('INSERT OR REPLACE INTO redirects VALUES (?, ?)', [$old, $new]);
}

// ---- Terms page and contact email (from the old site, renamed to Alora) -------
$terms = wp_get('/wp-json/wp/v2/pages/2002?_fields=content')['content']['rendered'];
$terms = preg_replace('#<h(\d)[^>]*>#', '<h3>', preg_replace('#</h\d>#', '</h3>', $terms));
$terms = strip_tags(preg_replace('#<(script|style)[^>]*>.*?</\1>#is', '', $terms), '<p><br><ul><ol><li><strong><em><b><i><h3>');
$terms = preg_replace('#<(\w+)\s[^>]*>#', '<$1>', $terms);
$terms = str_replace(['Jolie Femme – Life’s Little Things', 'Jolie Femme'], 'Alora', $terms);
set_setting('terms_html', trim($terms));
if (setting('email') === null) {
    set_setting('email', 'hello.alora97@gmail.com');
}

$t = fn ($sql) => q($sql)->fetchColumn();
echo "DB: ", $t('SELECT COUNT(*) FROM products'), " products, ", $t('SELECT COUNT(*) FROM product_images'), " images, ",
    $t('SELECT COUNT(*) FROM categories'), " categories, ", $t('SELECT COUNT(*) FROM products WHERE price_cents = 0'), " with price R0\n";
