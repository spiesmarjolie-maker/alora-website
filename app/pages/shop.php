<?php
// /shop/ and /product-category/{slug}/ with search, sort, in-stock filter and paging.
$cat = null;
if (!empty($m[1])) {
    $cat = q('SELECT * FROM categories WHERE slug = ? AND active = 1', [$m[1]])->fetch() ?: not_found();
}

$where = ['1'];
$args = [];
if ($cat) {
    $where[] = 'p.id IN (SELECT product_id FROM product_categories WHERE category_id = ?)';
    $args[] = $cat['id'];
}
$search = trim((string) ($_GET['q'] ?? ''));
if ($search !== '') {
    $where[] = 'p.name LIKE ?';
    $args[] = '%' . $search . '%';
}
if (!empty($_GET['instock'])) {
    $where[] = '(p.stock_qty IS NULL OR p.stock_qty > 0)';
}
$sorts = [
    'new' => 'p.id DESC',
    'low' => 'p.price_cents ASC, p.id DESC',
    'high' => 'p.price_cents DESC, p.id DESC',
    'az' => 'p.name COLLATE NOCASE ASC',
];
$sort = array_key_exists($_GET['sort'] ?? '', $sorts) ? $_GET['sort'] : 'new';

$all = products(implode(' AND ', $where), $args, '(p.stock_qty IS NULL OR p.stock_qty > 0) DESC, ' . $sorts[$sort], 1000);
$per = 24;
$pages = max(1, (int) ceil(count($all) / $per));
$page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));

view('shop', [
    'title' => $cat ? $cat['name'] : ($search !== '' ? "Search: $search" : 'Shop all'),
    'description' => $cat ? "Shop {$cat['name']} at Alora: where dreams meet light." : 'Shop all Alora gifts, jewellery, nails, pamper and home pieces.',
    'cat' => $cat,
    'cats' => array_filter(categories(), fn ($c) => $c['n'] > 0),
    'items' => array_slice($all, ($page - 1) * $per, $per),
    'total' => count($all),
    'page' => $page,
    'pages' => $pages,
    'search' => $search,
    'sort' => $sort,
]);
