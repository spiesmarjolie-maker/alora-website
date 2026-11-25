<?php
/** Delivery options and their prices. */
if ($method === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
    $price = parse_rand($_POST['price'] ?? '');
    if (mb_strlen($name) < 2 || $price === null) {
        flash('A delivery option needs a name and a price (0 for free).');
        redirect('/admin/shipping/');
    }
    $f = [$name, mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 200), $price, empty($_POST['needs_address']) ? 0 : 1,
        empty($_POST['active']) ? 0 : 1, (int) ($_POST['sort'] ?? 0)];
    $id ? q('UPDATE shipping_methods SET name=?, description=?, price_cents=?, needs_address=?, active=?, sort=? WHERE id = ?', [...$f, $id])
        : q('INSERT INTO shipping_methods (name, description, price_cents, needs_address, active, sort) VALUES (?,?,?,?,?,?)', $f);
    flash("“{$name}” saved.");
    redirect('/admin/shipping/');
}
admin_view('shipping', ['title' => 'Delivery', 'methods' => q('SELECT * FROM shipping_methods ORDER BY sort, id')->fetchAll()]);
