<?php
/** Payfast ITN: Payfast's server tells us a payment's result. Always answer 200 quickly; log everything. */
require_once __DIR__ . '/../shop.php';
require_once __DIR__ . '/../payfast.php';

http_response_code(200);
if ($method !== 'POST') {
    exit;
}
$post = $_POST;
$log = fn (string $msg) => file_put_contents(STORAGE . '/payfast.log', date('c') . ' ' . $msg . "\n", FILE_APPEND);

$order = q('SELECT * FROM orders WHERE number = ?', [(string) ($post['m_payment_id'] ?? '')])->fetch();
if (!$order) {
    $log('unknown order ' . ($post['m_payment_id'] ?? '?'));
    exit;
}
if ($why = pf_check_itn($post, $order)) {
    $log("{$order['number']} rejected: $why");
    exit;
}
$status = $post['payment_status'] ?? '';
$log("{$order['number']} $status pf_payment_id=" . ($post['pf_payment_id'] ?? ''));
if ($status === 'COMPLETE') {
    mark_paid($order, (string) ($post['pf_payment_id'] ?? ''));
}
