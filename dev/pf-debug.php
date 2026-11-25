<?php
// Dev only: show the Payfast fields and signature string for the latest order.
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/shop.php';
require __DIR__ . '/../app/payfast.php';
$_SERVER['HTTP_HOST'] = 'localhost:8080';
function e(?string $s): string { return htmlspecialchars((string) $s); }
function money(int $c): string { return 'R' . $c / 100; }
$o = q('SELECT * FROM orders ORDER BY id DESC LIMIT 1')->fetch();
$f = pf_form($o);
print_r($f);
[, , $pass] = pf_creds();
$pairs = [];
foreach ($f as $k => $v) { if ($k !== 'signature' && $v !== '') $pairs[] = $k . '=' . urlencode(trim((string) $v)); }
echo implode('&', $pairs), ($pass !== '' ? '&passphrase=' . urlencode($pass) : ''), PHP_EOL;
