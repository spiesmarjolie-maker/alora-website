<?php
/** Orders: list, detail with status changes (emails the customer), invoice and packing slip. */
$id = (int) ($parts[1] ?? 0);

if (!$id) {
    $status = array_key_exists($_GET['status'] ?? '', ORDER_STATUSES) ? $_GET['status'] : '';
    $search = trim((string) ($_GET['q'] ?? ''));
    $where = ["status != 'pending'"];
    $args = [];
    if ($status) {
        $where = ['status = ?'];
        $args[] = $status;
    }
    if ($search !== '') {
        $where[] = '(number LIKE ? OR name LIKE ? OR email LIKE ? OR phone LIKE ?)';
        array_push($args, ...array_fill(0, 4, "%$search%"));
    }
    $counts = q('SELECT status, COUNT(*) n FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
    admin_view('orders', [
        'title' => 'Orders',
        'orders' => q('SELECT * FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 300', $args)->fetchAll(),
        'status' => $status, 'search' => $search, 'counts' => $counts,
    ]);
    return;
}

$order = order_by_id($id) ?? not_found();

if (in_array($parts[2] ?? '', ['invoice', 'packing'], true)) {
    $doc = $parts[2];
    require __DIR__ . '/views/print.php';
    return;
}

if ($method === 'POST') {
    $new = (string) ($_POST['status'] ?? '');
    $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 1000);
    $tracking = mb_substr(trim((string) ($_POST['tracking'] ?? '')), 0, 80);
    // An unpaid order can only be cancelled or noted; it becomes "paid" only through Payfast.
    $allowed = $order['status'] === 'pending' ? ['cancelled', 'note'] : ['packed', 'shipped', 'completed', 'cancelled', 'note'];
    if (!in_array($new, $allowed, true) || ($new !== 'note' && $order['status'] === 'cancelled')) {
        flash('That change is not possible for this order.');
        redirect("/admin/orders/$id/");
    }
    if ($new === 'note') {
        q('INSERT INTO order_events (order_id, status, note, by_staff) VALUES (?, ?, ?, ?)', [$id, $order['status'], $note ?: '(note)', $me['id']]);
        flash('Note added.');
        redirect("/admin/orders/$id/");
    }
    if ($new === 'shipped' && $tracking === '' && empty($_POST['no_tracking'])) {
        flash('Add the PUDO tracking number, or tick "no tracking number".');
        redirect("/admin/orders/$id/");
    }
    db()->beginTransaction();
    q("UPDATE orders SET status = ?, tracking = COALESCE(NULLIF(?, ''), tracking), updated_at = datetime('now') WHERE id = ?", [$new, $tracking, $id]);
    if ($new === 'cancelled' && !empty($_POST['restock']) && $order['status'] !== 'pending') {
        restock($id);
        $note = trim($note . ' (stock put back)');
    }
    $email = !empty($_POST['email_customer']) && $order['status'] !== 'pending';
    q('INSERT INTO order_events (order_id, status, note, by_staff, emailed) VALUES (?, ?, ?, ?, ?)', [$id, $new, $note, $me['id'], (int) $email]);
    db()->commit();
    if ($email) {
        send_order_email(order_by_id($id), $new, $note);
    }
    flash('Order ' . $order['number'] . ' is now: ' . ORDER_STATUSES[$new] . ($email ? '. The customer has been emailed.' : '.'));
    redirect("/admin/orders/$id/");
}

admin_view('order', [
    'title' => 'Order ' . $order['number'],
    'order' => $order,
    'items' => order_items($id),
    'addr' => json_decode((string) $order['address_json'], true) ?: [],
    'events' => q('SELECT e.*, s.name AS by_name FROM order_events e LEFT JOIN staff s ON s.id = e.by_staff WHERE order_id = ? ORDER BY e.id', [$id])->fetchAll(),
]);
