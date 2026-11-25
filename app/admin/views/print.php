<?php
/** Printable invoice or packing slip. Uses "Save as PDF" from the browser's print dialog. @var string $doc */
$items = order_items((int) $order['id']);
$addr = json_decode((string) $order['address_json'], true) ?: [];
$invoice = $doc === 'invoice';
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title><?= $invoice ? 'Invoice' : 'Packing slip' ?> <?= e($order['number']) ?></title>
<style>
  body { font: 14px/1.5 Helvetica, Arial, sans-serif; color: #1a1612; max-width: 760px; margin: 30px auto; padding: 0 20px; }
  .top { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1a1612; padding-bottom: 14px; }
  .brand { font: 26px Georgia, serif; letter-spacing: 8px; }
  h1 { font: 22px Georgia, serif; margin: 0 0 4px; text-align: right; }
  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
  th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #ddd; } .r { text-align: right; } .box { width: 22px; height: 22px; border: 1.5px solid #1a1612; }
  .cols { display: flex; gap: 40px; margin-top: 18px; } .cols div { flex: 1; } .muted { color: #6b604f; }
  .tot td { font-weight: bold; border-top: 2px solid #1a1612; }
  button { padding: 10px 18px; border-radius: 99px; border: 0; background: #1a1612; color: #fff; cursor: pointer; } @media print { button { display: none; } body { margin: 0; } }
</style></head>
<body>
<p><button onclick="print()">Print / save as PDF</button></p>
<div class="top">
  <div><div class="brand">ALORA</div><div class="muted"><?= e(setting('email')) ?><br><?= e(setting('phone')) ?></div></div>
  <div><h1><?= $invoice ? 'Invoice' : 'Packing slip' ?></h1><div class="r"><?= e($order['number']) ?><br><?= e(date('j F Y', strtotime($order['paid_at'] ?? $order['created_at']))) ?></div></div>
</div>
<div class="cols">
  <div><strong><?= $invoice ? 'Billed to' : 'Ship to' ?></strong><br><?= e($order['name']) ?><br><?= e($order['phone']) ?><br><?= e($order['email']) ?></div>
  <div><strong><?= e($order['shipping_name']) ?></strong><br><?= nl2br(e(implode("\n", array_filter([isset($addr['locker']) ? 'Locker: ' . $addr['locker'] : '', $addr['street'] ?? '', $addr['suburb'] ?? '', trim(($addr['city'] ?? '') . ' ' . ($addr['postcode'] ?? '')), $addr['province'] ?? ''])))) ?></div>
</div>
<table>
  <tr><?php if (!$invoice): ?><th></th><?php endif ?><th>Item</th><th class="r">Qty</th><?php if ($invoice): ?><th class="r">Price</th><th class="r">Total</th><?php endif ?></tr>
  <?php foreach ($items as $it): ?>
    <tr><?php if (!$invoice): ?><td><div class="box"></div></td><?php endif ?><td><?= e($it['name']) ?></td><td class="r"><?= (int) $it['qty'] ?></td>
      <?php if ($invoice): ?><td class="r"><?= money((int) $it['unit_cents']) ?></td><td class="r"><?= money($it['qty'] * $it['unit_cents']) ?></td><?php endif ?></tr>
  <?php endforeach ?>
  <?php if ($invoice): ?>
    <tr><td colspan="3">Subtotal</td><td class="r"><?= money((int) $order['subtotal_cents']) ?></td></tr>
    <?php if ($order['discount_cents']): ?><tr><td colspan="3">Discount (<?= e($order['coupon_code']) ?>)</td><td class="r">−<?= money((int) $order['discount_cents']) ?></td></tr><?php endif ?>
    <tr><td colspan="3"><?= e($order['shipping_name']) ?></td><td class="r"><?= money((int) $order['shipping_cents']) ?></td></tr>
    <tr class="tot"><td colspan="3">Total paid<?= $order['payfast_id'] ? ' (Payfast ' . e($order['payfast_id']) . ')' : '' ?></td><td class="r"><?= money((int) $order['total_cents']) ?></td></tr>
  <?php endif ?>
</table>
<?php if ($order['customer_note']): ?><p><strong>Note:</strong> <?= nl2br(e($order['customer_note'])) ?></p><?php endif ?>
<p class="muted"><?= $invoice ? 'Thank you for shopping with Alora.' : 'Packed with love by Alora 🦋' ?></p>
</body></html>
