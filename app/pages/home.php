<?php
// Home: the scroll film, then the shop sections. Film pieces use real products from these categories.
$pick = fn (string $cat, int $n) => products(
    'p.id IN (SELECT product_id FROM product_categories pc JOIN categories c ON c.id = pc.category_id WHERE c.slug = ?)
     AND (p.stock_qty IS NULL OR p.stock_qty > 0)', [$cat], 'p.id DESC', $n);

// The film only shows products that have a clean cut-out (no backgrounds or packaging floating in the light).
$cut = fn (string $cat, int $n) => array_slice(array_values(array_filter($pick($cat, 30),
    fn ($p) => $p['image'] && is_file(PUBLIC_DIR . "/media/{$p['image']}-cut-600.webp"))), 0, $n);

view('home', [
    'title' => setting('shop_name') . ', ' . setting('tagline'),
    'description' => 'Alora: where dreams meet light. Gifts, jewellery, press-on nails, pamper treats and personalised pieces. Shop online or order on WhatsApp.',
    'film' => [
        'jewellery' => $cut('accessories', 2),
        'nails' => $cut('nails', 1),
        'home' => $cut('home-glow', 1),
        'bath' => $cut('bath-body', 1),
        'book' => $cut('stationary-lovers', 1),
        'gift' => $cut('ready-packed-gifts', 1),
    ],
    'cats' => array_filter(categories(), fn ($c) => $c['n'] > 0),
    'newIn' => products('p.stock_qty IS NULL OR p.stock_qty > 0', [], 'p.id DESC', 8),
    'gifts' => $pick('ready-packed-gifts', 4),
    'dark' => true,
]);
