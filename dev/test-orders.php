<?php
declare(strict_types=1);
/** Dev self-check for order logic (stock, bundles, coupons) on a throwaway copy of the database.
 *   php -c dev/php.ini dev/test-orders.php */
$copy = sys_get_temp_dir() . '/alora-test.sqlite';
copy(__DIR__ . '/../storage/alora.sqlite', $copy);
putenv("ALORA_DB=$copy");
$_SERVER['HTTP_HOST'] = 'localhost:8080';
require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/shop.php';

$fails = 0;
$check = function (string $what, bool $ok) use (&$fails) { echo ($ok ? 'PASS ' : 'FAIL ') . $what . PHP_EOL; $fails += !$ok; };
$stock = fn (int $id) => q('SELECT stock_qty FROM products WHERE id = ?', [$id])->fetchColumn();
$line = fn (int $id, int $qty) => (function () use ($id, $qty) {
    $p = q('SELECT id, name, price_cents FROM products WHERE id = ?', [$id])->fetch();
    return $p + ['qty' => $qty, 'line_cents' => $qty * $p['price_cents']];
})();
$buyer = ['name' => 'Test Buyer', 'email' => 't@example.com', 'phone' => '0820000000', 'note' => '', 'address' => ['locker' => 'Test']];
$ship = q('SELECT * FROM shipping_methods ORDER BY sort LIMIT 1')->fetch();

// 1. Tracked stock goes down by the quantity bought.
$masks = (int) q("SELECT id FROM products WHERE name = 'Facial Masks'")->fetchColumn();
$before = (int) $stock($masks);
$o = create_order([$line($masks, 3)], $buyer, $ship, null, 0);
$check('order total = items + shipping', $o['total_cents'] === 3 * 1500 + (int) $ship['price_cents']);
mark_paid($o, 'T1');
$check("stock $before -> " . $stock($masks), (int) $stock($masks) === $before - 3);
mark_paid(order_by_id((int) $o['id']), 'T1');
$check('second notification does not take stock again', (int) $stock($masks) === $before - 3);

// 2. A bundle takes stock from what is inside it.
$lip = (int) q("SELECT id FROM products WHERE name = 'Lip Mask'")->fetchColumn();
$eye = (int) q("SELECT id FROM products WHERE name = 'Eye Masks'")->fetchColumn();
q("INSERT INTO products (slug, name, price_cents, regular_cents, stock_qty, is_bundle) VALUES ('test-bundle', 'Test bundle', 2500, 2500, NULL, 1)");
$bundle = (int) db()->lastInsertId();
q('INSERT INTO bundle_items VALUES (?, ?, 1), (?, ?, 2)', [$bundle, $lip, $bundle, $eye]);
[$l0, $e0] = [(int) $stock($lip), (int) $stock($eye)];
$o = create_order([$line($bundle, 2)], $buyer, $ship, null, 0);
mark_paid($o, 'T2');
$check("bundle x2 takes 2 lip masks ($l0 -> {$stock($lip)})", (int) $stock($lip) === $l0 - 2);
$check("bundle x2 takes 4 eye masks ($e0 -> {$stock($eye)})", (int) $stock($eye) === $e0 - 4);

// 3. Coupons.
q("INSERT INTO coupons (code, type, amount, min_spend_cents, usage_limit) VALUES ('GOLD10', 'percent', 10, 10000, 1), ('OLD', 'fixed', 5000, 0, NULL)");
q("UPDATE coupons SET expires_at = '2020-01-01' WHERE code = 'OLD'");
[$c, $off, $err] = coupon_for('gold10', 20000);
$check('10% off R200 = R20 (code is not case sensitive)', $c && $off === 2000);
[, , $err] = coupon_for('GOLD10', 5000);
$check('minimum spend enforced', (bool) $err);
[, , $err] = coupon_for('OLD', 20000);
$check('expired code refused', (bool) $err);
$o = create_order([$line($masks, 10)], $buyer, $ship, $c, 1500);
mark_paid($o, 'T3');
[, , $err] = coupon_for('GOLD10', 20000);
$check('usage limit reached after one use', (bool) $err);

// 4. Overselling is flagged, never negative.
q('UPDATE products SET stock_qty = 1 WHERE id = ?', [$masks]);
$o = create_order([$line($masks, 2)], $buyer, $ship, null, 0);
mark_paid($o, 'T4');
$check('stock floors at 0', (int) $stock($masks) === 0);
$check('shortfall noted on the order', str_contains((string) q("SELECT note FROM order_events WHERE order_id = ? AND status = 'paid'", [$o['id']])->fetchColumn(), 'Low stock'));

echo $fails ? "\n$fails FAILED\n" : "\nAll passed\n";
exit($fails ? 1 : 0);
