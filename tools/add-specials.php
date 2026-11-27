<?php
declare(strict_types=1);
/**
 * Create the special products once: the Mystery Scoop (R150 a scoop), the Build-a-Box gift box (R50),
 * and the Teachers Box (R150) with its four add-ons.
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
    // Teachers Box (owner's flyer, 28 Nov 2026): a personalised plastic stationery box set, plus optional add-ons.
    ['teachers-box', 'Personalised Teachers Stationery Box', 15000,
        '<p>A plastic stationery box personalised with your name, grade or subject and design, filled with 8 neon gel ink pens, a highlighter, 2 pens, a pencil, a pencil sharpener (with container), page flags and sticky tabs, and paper clips. Design yours on the <a href="/teachers-box/">Teachers Box page</a>.</p>'],
    ['teachers-box-sticker-pack', 'Teachers Box add-on: sticker pack', 2000, '<p>A sheet of reward stickers, packed in your Teachers Box.</p>'],
    ['teachers-box-sticky-notes', 'Teachers Box add-on: sticky note pad', 1000, '<p>A sticky note pad, packed in your Teachers Box.</p>'],
    ['teachers-box-eraser', 'Teachers Box add-on: eraser', 800, '<p>An eraser, packed in your Teachers Box.</p>'],
    ['teachers-box-stapler', 'Teachers Box add-on: stapler set', 3000, '<p>A mini stapler with staples, packed in your Teachers Box.</p>'],
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
