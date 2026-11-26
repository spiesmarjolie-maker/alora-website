<?php
require_once __DIR__ . '/../shop.php';
require_once __DIR__ . '/../payfast.php';

$lines = bag_lines();
if (!$lines) {
    redirect('/bag/');
}
$methods = q('SELECT * FROM shipping_methods WHERE active = 1 ORDER BY sort')->fetchAll();
$subtotal = array_sum(array_column($lines, 'line_cents'));
// Remember what they typed if they come back; signed-in customers start from their saved details.
$saved = [];
if (!customer()) {
    $_SESSION['after_login'] = '/checkout/';   // signing in from here brings them back
}
if ($c = customer()) {
    $saved = ['name' => $c['name'], 'email' => $c['email'], 'phone' => $c['phone']] + (json_decode((string) $c['address_json'], true) ?: []);
}
$in = $_POST + ($_SESSION['checkout'] ?? []) + $saved;
$errors = [];

if (!empty($_GET['cancelled'])) {
    flash('Payment was cancelled. Your bag is still here whenever you are ready.');
}

if ($method === 'POST') {
    csrf_check();
    $v = fn ($k, $max = 200) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $buyer = [
        'name' => $v('name', 120), 'email' => $v('email', 160), 'phone' => preg_replace('/[^\d+ ]/', '', $v('phone', 30)),
        'note' => $v('note', 1000), 'customer_id' => $_SESSION['customer_id'] ?? null,
    ];
    $boxId = (int) q("SELECT id FROM products WHERE slug = 'build-a-box-gift-box'")->fetchColumn();
    if (!empty($_SESSION['box']) && isset(bag()[$boxId])) {
        $buyer['note'] = trim('BUILD A BOX, pack together: ' . implode(', ', $_SESSION['box']) . "
" . $buyer['note']);
    }
    $ship = null;
    foreach ($methods as $m) {
        if ((int) $m['id'] === (int) ($_POST['ship'] ?? 0)) {
            $ship = $m;
        }
    }

    if (mb_strlen($buyer['name']) < 2) $errors['name'] = 'Please enter your name.';
    if (!filter_var($buyer['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (strlen(preg_replace('/\D/', '', $buyer['phone'])) < 9) $errors['phone'] = 'Please enter your cell number.';
    if (!$ship) $errors['ship'] = 'Please choose a delivery option.';
    if (empty($_POST['agree'])) $errors['agree'] = 'Please accept the terms and privacy notice.';

    if ($ship && !$ship['needs_address']) {
        $buyer['address'] = ['locker' => $v('locker', 160)];
        if (mb_strlen($buyer['address']['locker']) < 3) $errors['locker'] = 'Tell us which PUDO locker (area or name) suits you.';
    } else {
        $buyer['address'] = ['street' => $v('street'), 'suburb' => $v('suburb', 100), 'city' => $v('city', 100),
            'postcode' => $v('postcode', 10), 'province' => $v('province', 40)];
        if (mb_strlen($buyer['address']['street']) < 4) $errors['street'] = 'Please enter your street address.';
        if (mb_strlen($buyer['address']['city']) < 2) $errors['city'] = 'Please enter your town or city.';
        if (!preg_match('/^\d{4}$/', $buyer['address']['postcode'])) $errors['postcode'] = 'Postal codes have 4 digits.';
        if (!in_array($buyer['address']['province'], PROVINCES, true)) $errors['province'] = 'Please choose your province.';
    }

    [$coupon, $discount, $couponErr] = coupon_for($v('coupon', 40), $subtotal);
    if ($couponErr) $errors['coupon'] = $couponErr;

    // If stock changed since they filled the bag, show them before taking money.
    $bag = bag();
    foreach ($lines as $l) {
        if ($l['qty'] < ($bag[$l['id']] ?? 0)) {
            $errors['stock'] = "We only have {$l['qty']} of {$l['name']} left; your bag has been updated.";
            $_SESSION['bag'][$l['id']] = $l['qty'];
        }
    }

    $_SESSION['checkout'] = array_diff_key($_POST, ['csrf' => 1, 'agree' => 1]);
    if (!$errors) {
        $order = create_order($lines, $buyer, $ship, $coupon, $discount);
        $_SESSION['last_order'] = $order['id'];
        view('pay', ['title' => 'Taking you to Payfast', 'order' => $order, 'action' => 'https://' . pf_host() . '/eng/process', 'fields' => pf_form($order)]);
        return;
    }
}

view('checkout', [
    'title' => 'Checkout',
    'lines' => $lines,
    'subtotal' => $subtotal,
    'methods' => $methods,
    'in' => $in,
    'errors' => $errors,
    'sandbox' => pf_sandbox(),
]);
