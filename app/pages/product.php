<?php
$p = q('SELECT * FROM products WHERE slug = ? AND active = 1', [$m[1]])->fetch() ?: not_found();
$images = q('SELECT path, alt FROM product_images WHERE product_id = ? ORDER BY sort', [$p['id']])->fetchAll();
$cats = q('SELECT c.slug, c.name FROM categories c JOIN product_categories pc ON pc.category_id = c.id
           WHERE pc.product_id = ? ORDER BY c.sort', [$p['id']])->fetchAll();
$related = $cats ? products(
    'p.id != ? AND (p.stock_qty IS NULL OR p.stock_qty > 0) AND p.id IN
     (SELECT product_id FROM product_categories pc JOIN categories c ON c.id = pc.category_id WHERE c.slug = ?)',
    [$p['id'], $cats[0]['slug']], 'RANDOM()', 4) : [];

view('product', [
    'title' => $p['name'],
    'description' => trim(strip_tags($p['short_desc'] ?: $p['description'])) ?: "{$p['name']} at Alora: where dreams meet light.",
    'ogImage' => $images ? "/media/{$images[0]['path']}-1200.webp" : null,
    'p' => $p,
    'images' => $images,
    'cats' => $cats,
    'related' => $related,
]);
