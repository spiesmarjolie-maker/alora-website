<?php
/** Discount codes: % or rand off, minimum spend, expiry date, usage limit, on/off. */
if ($method === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_POST['code'] ?? '')));
    $type = ($_POST['type'] ?? '') === 'fixed' ? 'fixed' : 'percent';
    $amount = $type === 'percent' ? (int) ($_POST['amount'] ?? 0) : parse_rand($_POST['amount'] ?? '');
    $min = parse_rand($_POST['min'] ?? '') ?? 0;
    $expires = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['expires'] ?? '')) ? $_POST['expires'] : null;
    $limit = ($_POST['limit'] ?? '') === '' ? null : max(1, (int) $_POST['limit']);
    $err = match (true) {
        strlen($code) < 3 => 'Codes need at least 3 letters or numbers.',
        $type === 'percent' && ($amount < 1 || $amount > 100) => 'A percentage must be between 1 and 100.',
        $type === 'fixed' && !$amount => 'Enter the rand amount off.',
        (bool) q('SELECT 1 FROM coupons WHERE code = ? AND id != ?', [$code, $id])->fetchColumn() => "The code $code already exists.",
        default => null,
    };
    if ($err) {
        flash($err);
        redirect('/admin/coupons/');
    }
    $f = [$code, $type, $amount, $min, $expires, $limit, empty($_POST['active']) ? 0 : 1];
    $id ? q('UPDATE coupons SET code=?, type=?, amount=?, min_spend_cents=?, expires_at=?, usage_limit=?, active=? WHERE id = ?', [...$f, $id])
        : q('INSERT INTO coupons (code, type, amount, min_spend_cents, expires_at, usage_limit, active) VALUES (?,?,?,?,?,?,?)', $f);
    flash("Code $code saved.");
    redirect('/admin/coupons/');
}
admin_view('coupons', ['title' => 'Discount codes', 'coupons' => q('SELECT * FROM coupons ORDER BY active DESC, id DESC')->fetchAll()]);
