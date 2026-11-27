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

// A customer's Teachers Box picture (kept outside public/): staff only, served as a download-safe image.
if (($parts[0] ?? '') === 'uploads') {
    $name = $parts[1] ?? '';
    $file = STORAGE . '/uploads/teachers-box/' . $name;
    if (!preg_match('/^\d{8}-[a-f0-9]{16}\.(jpg|png|webp|heic)$/', $name) || !is_file($file)) {
        not_found();
    }
    $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic'];
    header('Content-Type: ' . $types[pathinfo($name, PATHINFO_EXTENSION)]);
    header('Content-Disposition: ' . (str_ends_with($name, '.heic') ? 'attachment' : 'inline') . '; filename="teachers-box-' . $name . '"');
    header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return;
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
