<?php
// The bag. POST actions: add, set (quantity), remove. Answers JSON to fetch() calls, redirects otherwise.
if ($method === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $p = q('SELECT id, name, stock_qty FROM products WHERE id = ? AND active = 1 AND price_cents > 0', [$id])->fetch();
    $bag = bag();
    $msg = null;
    if ($p) {
        $max = $p['stock_qty'] === null ? 99 : (int) $p['stock_qty'];
        $want = match ($_POST['action'] ?? 'add') {
            'set' => (int) $_POST['qty'],
            'remove' => 0,
            default => ($bag[$id] ?? 0) + max(1, (int) ($_POST['qty'] ?? 1)),
        };
        $qty = max(0, min($want, $max));
        if ($qty) {
            $bag[$id] = $qty;
        } else {
            unset($bag[$id]);
        }
        $_SESSION['bag'] = $bag;
        $msg = $want > $max ? "Only $max of {$p['name']} available." : ($qty ? "{$p['name']} is in your bag." : "{$p['name']} removed.");
    }
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['count' => bag_count(), 'message' => $msg ?? 'That item is no longer available.']);
        exit;
    }
    flash($msg);
    redirect('/bag/');
}

$lines = bag_lines();
view('bag', [
    'title' => 'Your bag',
    'lines' => $lines,
    'subtotal' => array_sum(array_column($lines, 'line_cents')),
    'shipping' => q('SELECT * FROM shipping_methods WHERE active = 1 ORDER BY sort')->fetchAll(),
]);
