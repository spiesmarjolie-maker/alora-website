<?php
/** Order status page. Guests get there with the private link (?t=token); it is also where Payfast sends them back. */
require_once __DIR__ . '/../shop.php';

$order = q('SELECT * FROM orders WHERE number = ?', [$m[1]])->fetch();
$mine = $order && (
    hash_equals($order['access_token'], (string) ($_GET['t'] ?? ''))
    || (($_SESSION['customer_id'] ?? null) && (int) $order['customer_id'] === (int) $_SESSION['customer_id'])
);
if (!$mine) {
    not_found();
}
// Back from Payfast: the bag has been ordered.
if (($_SESSION['last_order'] ?? null) == $order['id']) {
    unset($_SESSION['bag'], $_SESSION['checkout']);
}
header('Cache-Control: no-store');
view('order', [
    'title' => 'Order ' . $order['number'],
    'order' => $order,
    'items' => order_items((int) $order['id']),
    'events' => q('SELECT status, note, created_at FROM order_events WHERE order_id = ? ORDER BY id', [$order['id']])->fetchAll(),
]);
