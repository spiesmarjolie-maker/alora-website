<?php
/** /admin/… : staff only. Every change is a POST with a CSRF token; owners alone manage settings and staff. */
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
require_once __DIR__ . '/helpers.php';

$sub = trim(substr($path, strlen('/admin/')), '/');
$parts = $sub === '' ? [] : explode('/', $sub);
if ($method === 'POST') {
    csrf_check();
}

// Pages open without a login.
if (in_array($parts[0] ?? '', ['login', 'forgot', 'reset'], true)) {
    require __DIR__ . '/auth-pages.php';
    return;
}
$me = staff();
if (!$me) {
    $_SESSION['admin_after_login'] = $path;
    redirect('/admin/login/');
}
if (($parts[0] ?? '') === 'logout') {
    if ($method === 'POST') {
        unset($_SESSION['staff_id']);
        session_regenerate_id(true);
    }
    redirect('/admin/login/');
}

$section = $parts[0] ?? 'dashboard';
$file = __DIR__ . '/' . match ($section) {
    'dashboard' => 'dashboard.php',
    'orders' => 'orders.php',
    'products' => 'products.php',
    'categories' => 'categories.php',
    'coupons' => 'coupons.php',
    'shipping' => 'shipping.php',
    'settings' => 'settings.php',
    'staff' => 'staff.php',
    default => 'missing.php',
};
if (in_array($section, ['settings', 'staff'], true) && $me['role'] !== 'owner') {
    http_response_code(403);
    admin_view('message', ['title' => 'Owners only', 'message' => 'Only the shop owner can change this.']);
    return;
}
require $file;
