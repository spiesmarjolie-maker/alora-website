<?php
// Home: the scroll film, then the shop sections. Film pieces use real products from these categories.
$pick = fn (string $cat, int $n) => products(
    'p.id IN (SELECT product_id FROM product_categories pc JOIN categories c ON c.id = pc.category_id WHERE c.slug = ?)
     AND (p.stock_qty IS NULL OR p.stock_qty > 0)', [$cat], 'p.id DESC', $n);

view('home', [
    'title' => setting('shop_name') . ', ' . setting('tagline'),
    'description' => 'Alora: where dreams meet light. Gifts, jewellery, press-on nails, pamper treats and personalised pieces. Shop online or order on WhatsApp.',
    'film' => [
        'jewellery' => $pick('accessories', 2),
        'nails' => $pick('nails', 1),
        'home' => $pick('home-glow', 1),
        'bath' => $pick('bath-body', 1),
        'book' => $pick('stationary-lovers', 1),
        'gift' => $pick('ready-packed-gifts', 1),
    ],
    'cats' => array_filter(categories(), fn ($c) => $c['n'] > 0),
    'newIn' => products('p.stock_qty IS NULL OR p.stock_qty > 0', [], 'p.id DESC', 8),
    'gifts' => $pick('ready-packed-gifts', 4),
    'dark' => true,
]);
