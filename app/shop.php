<?php
declare(strict_types=1);
/** Orders: totals, coupons, creating orders, payment, stock. */

require_once __DIR__ . '/mail.php';

const PROVINCES = ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'];

function is_dev(): bool
{
    return PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli';
}

function site_url(string $path = '/'): string
{
    if ($u = setting('site_url')) {
        return rtrim($u, '/') . $path;
    }
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path;
}

/** Returns [coupon row, discount in cents] or [null, 0, error message]. */
function coupon_for(string $code, int $subtotal): array
{
    $code = trim($code);
    if ($code === '') {
        return [null, 0, null];
    }
    $c = q('SELECT * FROM coupons WHERE code = ? AND active = 1', [$code])->fetch();
    $err = match (true) {
        !$c => 'That discount code does not exist.',
        $c['expires_at'] && $c['expires_at'] < date('Y-m-d') => 'That discount code has expired.',
        $c['usage_limit'] !== null && $c['used_count'] >= $c['usage_limit'] => 'That discount code has been used up.',
        $subtotal < $c['min_spend_cents'] => 'That code needs a minimum spend of ' . money((int) $c['min_spend_cents']) . '.',
        default => null,
    };
    if ($err) {
        return [null, 0, $err];
    }
    $off = $c['type'] === 'percent' ? intdiv($subtotal * (int) $c['amount'], 100) : (int) $c['amount'];
    return [$c, min($off, $subtotal), null];
}

/**
 * Create a pending order from the bag. Prices and stock are re-read from the database,
 * never trusted from the browser. Returns the order row.
 */
function create_order(array $lines, array $buyer, array $ship, ?array $coupon, int $discount): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $no = (int) setting('next_order_no', '10001');
        set_setting('next_order_no', (string) ($no + 1));
        $subtotal = array_sum(array_column($lines, 'line_cents'));
        $total = $subtotal - $discount + (int) $ship['price_cents'];
        q('INSERT INTO orders (number, customer_id, email, name, phone, address_json, shipping_name, shipping_cents,
             subtotal_cents, discount_cents, total_cents, coupon_code, customer_note, access_token)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            setting('order_prefix', 'AL-') . $no, $buyer['customer_id'] ?? null, $buyer['email'], $buyer['name'], $buyer['phone'],
            json_encode($buyer['address'], JSON_UNESCAPED_UNICODE), $ship['name'], (int) $ship['price_cents'],
            $subtotal, $discount, $total, $coupon['code'] ?? null, $buyer['note'], bin2hex(random_bytes(16)),
        ]);
        $id = (int) $pdo->lastInsertId();
        foreach ($lines as $l) {
            q('INSERT INTO order_items (order_id, product_id, name, unit_cents, qty) VALUES (?,?,?,?,?)',
                [$id, $l['id'], $l['name'], $l['price_cents'], $l['qty']]);
        }
        q("INSERT INTO order_events (order_id, status, note) VALUES (?, 'pending', 'Order placed, waiting for payment')", [$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return order_by_id($id);
}

function order_by_id(int $id): ?array
{
    return q('SELECT * FROM orders WHERE id = ?', [$id])->fetch() ?: null;
}

function order_items(int $orderId): array
{
    return q('SELECT oi.*, p.slug, (SELECT path FROM product_images i WHERE i.product_id = oi.product_id ORDER BY sort LIMIT 1) AS image
              FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [$orderId])->fetchAll();
}

/** Take stock for each item; a bundle takes stock from the products inside it. Untracked stock (NULL) is left alone. */
function deduct_stock(int $orderId): array
{
    $short = [];
    foreach (q('SELECT product_id, qty, name FROM order_items WHERE order_id = ? AND product_id IS NOT NULL', [$orderId]) as $it) {
        $parts = q('SELECT product_id, qty FROM bundle_items WHERE bundle_id = ?', [$it['product_id']])->fetchAll()
            ?: [['product_id' => $it['product_id'], 'qty' => 1]];
        foreach ($parts as $part) {
            $need = $it['qty'] * $part['qty'];
            $left = q('SELECT stock_qty FROM products WHERE id = ?', [$part['product_id']])->fetchColumn();
            if ($left === null || $left === false) {
                continue;
            }
            if ((int) $left < $need) {
                $short[] = $it['name'];
            }
            q('UPDATE products SET stock_qty = MAX(0, stock_qty - ?), updated_at = datetime(\'now\') WHERE id = ?', [$need, $part['product_id']]);
        }
    }
    return $short;
}

/**
 * Payment confirmed: mark paid once (Payfast may notify more than once), take stock, count the coupon, send emails.
 */
function mark_paid(array $order, string $payfastId): void
{
    $pdo = db();
    $pdo->beginTransaction();
    $done = q("UPDATE orders SET status = 'paid', payfast_id = ?, paid_at = datetime('now'), updated_at = datetime('now')
               WHERE id = ? AND status = 'pending'", [$payfastId, $order['id']])->rowCount();
    if (!$done) {
        $pdo->rollBack();
        return;
    }
    $short = deduct_stock((int) $order['id']);
    if ($order['coupon_code']) {
        q('UPDATE coupons SET used_count = used_count + 1 WHERE code = ?', [$order['coupon_code']]);
    }
    $note = 'Payment received via Payfast (' . $payfastId . ')' . ($short ? '. Low stock on: ' . implode(', ', $short) : '');
    q("INSERT INTO order_events (order_id, status, note) VALUES (?, 'paid', ?)", [$order['id'], $note]);
    $pdo->commit();

    $order = order_by_id((int) $order['id']);
    send_order_email($order, 'paid');
    send_shop_email($order, $short);
}

function order_url(array $order): string
{
    return site_url('/order/' . rawurlencode($order['number']) . '/?t=' . $order['access_token']);
}
