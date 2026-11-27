<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/images.php';
require_once __DIR__ . '/shop.php';
require_once __DIR__ . '/auth.php';

date_default_timezone_set('Africa/Johannesburg');

$https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_name('alora');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

// ---- Output helpers ---------------------------------------------------------

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');   // &#039; works in every email app
}

function money(int $cents): string
{
    return 'R' . number_format($cents / 100, 2, ',', ' ');
}

function asset(string $path): string
{
    $file = PUBLIC_DIR . '/assets/' . $path;
    return '/assets/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Responsive <img> for a media base path like "p/bracelets". */
function img(?string $path, string $alt, string $sizes = '(min-width: 900px) 25vw, 50vw', string $attrs = ''): string
{
    if (!$path) {
        return '<div class="img-missing" aria-hidden="true"></div>';
    }
    $set = implode(', ', array_map(fn ($w) => "/media/$path-$w.webp {$w}w", IMAGE_WIDTHS));
    return '<img src="/media/' . e($path) . '-600.webp" srcset="' . e($set) . '" sizes="' . e($sizes) . '" alt="' . e($alt) . '" ' . $attrs . '>';
}

function whatsapp_link(string $text): string
{
    return 'https://wa.me/' . setting('whatsapp', '27817371752') . '?text=' . rawurlencode($text);
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . "/views/$name.php";
    $content = ob_get_clean();
    require __DIR__ . '/views/layout.php';
}

function redirect(string $to, int $code = 303): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function not_found(): never
{
    http_response_code(404);
    view('404', ['title' => 'Page not found']);
    exit;
}

// ---- Security ---------------------------------------------------------------

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Your session expired. Please go back and try again.');
    }
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

// ---- Catalogue --------------------------------------------------------------

const PRODUCT_COLS = 'p.id, p.slug, p.name, p.price_cents, p.regular_cents, p.stock_qty,
    (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort LIMIT 1) AS image';

function categories(): array
{
    return q('SELECT c.*, (SELECT COUNT(*) FROM product_categories pc JOIN products p ON p.id = pc.product_id
              WHERE pc.category_id = c.id AND p.active = 1) AS n
              FROM categories c WHERE c.active = 1 ORDER BY c.sort')->fetchAll();
}

function in_stock(array $p): bool
{
    return $p['stock_qty'] === null || (int) $p['stock_qty'] > 0;
}

/** Products a customer can actually buy (active and priced). */
/** Bought from their own pages (/mystery-scoop/, /build-a-box/, /teachers-box/), so they stay out of the shop grids. */
const SPECIAL_SLUGS = "'mystery-scoop', 'build-a-box-gift-box', 'teachers-box', 'teachers-box-sticker-pack', 'teachers-box-sticky-notes', 'teachers-box-eraser', 'teachers-box-stapler'";

function products(string $where = '1', array $args = [], string $order = 'p.id DESC', int $limit = 500, bool $withSpecials = false): array
{
    return q("SELECT " . PRODUCT_COLS . " FROM products p
              WHERE p.active = 1 AND p.price_cents > 0 " . ($withSpecials ? "" : "AND p.slug NOT IN (" . SPECIAL_SLUGS . ")") . " AND ($where) ORDER BY $order LIMIT $limit", $args)->fetchAll();
}

// ---- Bag (stored in the session as product id => quantity) -------------------

function bag(): array
{
    return $_SESSION['bag'] ?? [];
}

function bag_count(): int
{
    return array_sum(bag());
}

/** Bag lines with current prices and stock, quantities capped at what is in stock. */
function bag_lines(): array
{
    $bag = bag();
    if (!$bag) {
        return [];
    }
    $ids = implode(',', array_map('intval', array_keys($bag)));
    $lines = [];
    foreach (products("p.id IN ($ids)", [], "p.id DESC", 500, true) as $p) {
        $qty = $bag[$p['id']];
        if ($p['stock_qty'] !== null) {
            $qty = min($qty, (int) $p['stock_qty']);
        }
        if ($qty > 0) {
            $lines[] = $p + ['qty' => $qty, 'line_cents' => $qty * $p['price_cents']];
        }
    }
    return $lines;
}
