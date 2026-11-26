<?php
// The Mystery Scoop (choose 1-7 scoops) and Build a Box (pick any pieces, we pack them in a R50 gift box).
$special = fn (string $slug) => q("SELECT " . PRODUCT_COLS . " FROM products p WHERE p.slug = ? AND p.active = 1", [$slug])->fetch();

if ($path === '/mystery-scoop/') {
    $scoop = $special('mystery-scoop') ?: not_found();
    view('mystery-scoop', [
        'title' => 'Mystery Scoop',
        'description' => 'Alora Mystery Scoops: a scoop of surprise goodies for ' . money((int) $scoop['price_cents']) . '. Choose 1 to 7 scoops.',
        'scoop' => $scoop,
    ]);
    return;
}

// ---- Build a Box ----
$box = $special('build-a-box-gift-box') ?: not_found();

if ($method === 'POST') {
    csrf_check();
    $ids = array_slice(array_unique(array_map('intval', (array) ($_POST['items'] ?? []))), 0, 40);
    $picked = $ids ? products('p.id IN (' . implode(',', $ids) . ') AND (p.stock_qty IS NULL OR p.stock_qty > 0)') : [];
    if (!$picked) {
        flash('Pick at least one piece for your box.');
        redirect('/build-a-box/');
    }
    $bag = bag();
    $bag[(int) $box['id']] = ($bag[(int) $box['id']] ?? 0) + 1;
    foreach ($picked as $p) {
        $bag[(int) $p['id']] = ($bag[(int) $p['id']] ?? 0) + 1;
    }
    $_SESSION['bag'] = $bag;
    $_SESSION['box'] = array_merge($_SESSION['box'] ?? [], array_column($picked, 'name'));
    flash('Your box is in your bag: ' . count($picked) . ' ' . (count($picked) === 1 ? 'piece' : 'pieces') . ' plus the gift box.');
    redirect('/bag/');
}

// Every piece that is in stock, with its categories (for the filter) and its cut-out (to sit inside the drawn box).
$items = products('p.stock_qty IS NULL OR p.stock_qty > 0', [], 'p.name');
$cats = [];
foreach (q('SELECT pc.product_id, c.slug FROM product_categories pc JOIN categories c ON c.id = pc.category_id') as $r) {
    $cats[$r['product_id']][] = $r['slug'];
}
foreach ($items as &$p) {
    $p['cats'] = $cats[$p['id']] ?? [];
    $p['cut'] = $p['image'] && is_file(PUBLIC_DIR . "/media/{$p['image']}-cut-600.webp") ? "/media/{$p['image']}-cut-600.webp" : null;
}
unset($p);

view('build-a-box', [
    'title' => 'Build a Box',
    'description' => 'Build your own Alora gift box: pick any pieces from the shop and we pack them in a beautiful box for ' . money((int) $box['price_cents']) . '.',
    'box' => $box,
    'items' => $items,
    'cats' => array_filter(categories(), fn ($c) => $c['n'] > 0),
]);
