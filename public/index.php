<?php
declare(strict_types=1);

// Local dev server (php -S) serves real files itself.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require __DIR__ . '/../app/bootstrap.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$method = $_SERVER['REQUEST_METHOD'];

// Old WordPress addresses.
if ($to = q('SELECT new_path FROM redirects WHERE old_path = ?', [$path])->fetchColumn()) {
    redirect($to, 301);
}
// One canonical form: trailing slash on pages.
if ($path !== '/' && !str_ends_with($path, '/') && !str_contains(basename($path), '.')) {
    redirect($path . '/' . (($_SERVER['QUERY_STRING'] ?? '') ? '?' . $_SERVER['QUERY_STRING'] : ''), 301);
}

// Privacy-friendly stats: one counter per page per day, nothing about the visitor.
if ($method === 'GET' && !preg_match('#^/(admin|account|order|payfast|bag|checkout)/#', $path)) {
    register_shutdown_function(function () use ($path) {
        if (http_response_code() === 200) {
            q('INSERT INTO page_views (day, path, views) VALUES (?, ?, 1) ON CONFLICT(day, path) DO UPDATE SET views = views + 1', [date('Y-m-d'), $path]);
        }
    });
}

$m = [];
match (true) {
    $path === '/' => require __DIR__ . '/../app/pages/home.php',
    $path === '/shop/' => require __DIR__ . '/../app/pages/shop.php',
    (bool) preg_match('#^/product-category/([a-z0-9-]+)/$#', $path, $m) => require __DIR__ . '/../app/pages/shop.php',
    (bool) preg_match('#^/product/([a-z0-9-]+)/$#', $path, $m) => require __DIR__ . '/../app/pages/product.php',
    in_array($path, ['/mystery-scoop/', '/build-a-box/'], true) => require __DIR__ . '/../app/pages/specials.php',
    $path === '/bag/' => require __DIR__ . '/../app/pages/bag.php',
    $path === '/checkout/' => require __DIR__ . '/../app/pages/checkout.php',
    str_starts_with($path, '/account/') => require __DIR__ . '/../app/pages/account.php',
    str_starts_with($path, '/admin/') => require __DIR__ . '/../app/admin/router.php',
    $path === '/payfast/notify/' => require __DIR__ . '/../app/pages/payfast-notify.php',
    (bool) preg_match('#^/order/([A-Z]{1,4}-\d+)/$#', $path, $m) => require __DIR__ . '/../app/pages/order.php',
    in_array($path, ['/about/', '/personalised/', '/contact/', '/terms/', '/privacy/'], true)
        => view(trim($path, '/'), ['title' => ucfirst(trim($path, '/'))]),
    default => not_found(),
};
