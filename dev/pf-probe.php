<?php
// Dev only: which sandbox signature setup does Payfast accept? Posts test forms to the sandbox and reports.
function sig(array $f, string $pass): string
{
    $p = [];
    foreach ($f as $k => $v) { if ($v !== '') $p[] = $k . '=' . urlencode(trim($v)); }
    return md5(implode('&', $p) . ($pass !== '' ? '&passphrase=' . urlencode($pass) : ''));
}
function probe(array $f): string
{
    $c = curl_init('https://sandbox.payfast.co.za/eng/process');
    curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($f), CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA, CURLOPT_TIMEOUT => 30]);
    $body = (string) curl_exec($c);
    $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
    $sigErr = str_contains($body, 'signature') ? ' SIGNATURE-ERROR' : '';
    preg_match_all('#<li[^>]*>(.*?)</li>#s', $body, $m);
    return $code . $sigErr . ' ' . curl_error($c) . ' ' . trim(strip_tags(implode(' | ', array_slice($m[1], 0, 3))));
}
foreach (['local' => 'http://localhost:8080', 'public' => 'https://example.com'] as $label => $base) {
    foreach (['nopass' => '', 'jt7' => 'jt7NOE43FZPn'] as $pl => $pass) {
        $f = ['merchant_id' => '10000100', 'merchant_key' => '46f0cd694581a',
            'return_url' => "$base/order/AL-1/?t=abc", 'cancel_url' => "$base/checkout/", 'notify_url' => "$base/payfast/notify/",
            'name_first' => 'Test', 'name_last' => 'Customer', 'email_address' => 'test.customer@example.com',
            'm_payment_id' => 'AL-PROBE', 'amount' => '10.00', 'item_name' => 'Alora test'];
        $f['signature'] = sig($f, $pass);
        echo str_pad("$label/$pl", 16), probe($f), PHP_EOL;
    }
}
