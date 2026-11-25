<?php
declare(strict_types=1);
/**
 * Dev only: pretend to be Payfast and send a payment notification (ITN) to the local site,
 * because the real Payfast cannot reach a PC at home.
 *   php -c dev/php.ini dev/simulate-itn.php AL-10003 [COMPLETE|CANCELLED] [amount]
 */
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/payfast.php';

$number = $argv[1] ?? exit("Usage: simulate-itn.php ORDER [STATUS] [AMOUNT]\n");
$o = q('SELECT * FROM orders WHERE number = ?', [$number])->fetch() ?: exit("No order $number\n");
[$id, , $pass] = pf_creds();
$name = explode(' ', $o['name'], 2);
$post = [
    'm_payment_id' => $o['number'], 'pf_payment_id' => (string) random_int(1000000, 9999999),
    'payment_status' => $argv[2] ?? 'COMPLETE', 'item_name' => 'Alora order ' . $o['number'], 'item_description' => '',
    'amount_gross' => $argv[3] ?? number_format($o['total_cents'] / 100, 2, '.', ''),
    'amount_fee' => '-5.52', 'amount_net' => '0.00',
    'custom_str1' => '', 'name_first' => $name[0], 'name_last' => $name[1] ?? '', 'email_address' => $o['email'], 'merchant_id' => $id,
];
$post['signature'] = md5(pf_itn_string($post) . ($pass !== '' ? '&passphrase=' . urlencode($pass) : ''));

$c = curl_init('http://localhost:8080/payfast/notify/');
curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post), CURLOPT_RETURNTRANSFER => true]);
curl_exec($c);
echo 'HTTP ', curl_getinfo($c, CURLINFO_HTTP_CODE), "\n";
echo 'Order is now: ', q('SELECT status FROM orders WHERE number = ?', [$number])->fetchColumn(), "\n";
