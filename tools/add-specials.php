<?php
declare(strict_types=1);
/**
 * Create the two special products once: the Mystery Scoop (R150 a scoop) and the Build-a-Box gift box (R50).
 * They are normal products (so bag, checkout, Payfast, stock and admin all just work), but they are bought
 * from their own pages, /mystery-scoop/ and /build-a-box/, and stay out of the shop grids (see SPECIAL_SLUGS).
 *   php -c dev/php.ini tools/add-specials.php
 */
require __DIR__ . '/../app/db.php';

$items = [
    ['mystery-scoop', 'Mystery Scoop', 15000,
        '<p>A scoop of surprise goodies, hand-picked and packed by us. Every scoop is different. Choose 1 to 7 scoops on the <a href="/mystery-scoop/">Mystery Scoop page</a>.</p>'],
    ['build-a-box-gift-box', 'Build-a-Box gift box', 5000,
        '<p>The Alora gift box for your own Build a Box: we pack the pieces you chose into it with ribbon and love. Build yours on the <a href="/build-a-box/">Build a Box page</a>.</p>'],
];
foreach ($items as [$slug, $name, $cents, $desc]) {
    if (q('SELECT id FROM products WHERE slug = ?', [$slug])->fetchColumn()) {
        echo "$name already exists\n";
        continue;
    }
    q('INSERT INTO products (slug, name, short_desc, description, price_cents, regular_cents, stock_qty, active) VALUES (?,?,?,?,?,?,NULL,1)',
        [$slug, $name, '', $desc, $cents, $cents]);
    echo "Created $name\n";
}
