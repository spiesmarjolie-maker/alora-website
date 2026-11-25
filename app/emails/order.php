<?php
/** Customer email for an order status. @var array $order @var array $items @var string $kind @var string $note */
$first = explode(' ', trim($order['name']))[0];
$intro = [
    'paid' => "Thank you, $first! We've received your payment and we're getting your order ready with love.",
    'packed' => "Good news, $first: your order is packed and waiting for the courier.",
    'shipped' => "Your order is on its way, $first!",
    'completed' => "Your order is complete. We hope you love it, $first!",
    'cancelled' => "Your order has been cancelled, $first. If you paid, your refund will follow. Please reply if you have any questions.",
][$kind] ?? "There's an update on your order, $first.";
$addr = json_decode((string) $order['address_json'], true) ?: [];
?>
<h1 style="font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:26px;margin:0 0 12px;">Order <?= e($order['number']) ?></h1>
<p style="margin:0 0 18px;"><?= e($intro) ?></p>
<?php if ($kind === 'shipped' && $order['tracking']): ?>
  <p style="margin:0 0 18px;padding:12px 14px;background:#f8f4ec;border-radius:8px;">Tracking number: <strong><?= e($order['tracking']) ?></strong></p>
<?php endif ?>
<?php if ($note): ?><p style="margin:0 0 18px;"><?= nl2br(e($note)) ?></p><?php endif ?>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
  <?php foreach ($items as $it): ?>
  <tr><td style="padding:8px 0;border-bottom:1px solid #efe7d8;"><?= (int) $it['qty'] ?> × <?= e($it['name']) ?></td>
      <td align="right" style="padding:8px 0;border-bottom:1px solid #efe7d8;white-space:nowrap;"><?= money($it['qty'] * $it['unit_cents']) ?></td></tr>
  <?php endforeach ?>
  <tr><td style="padding:6px 0;color:#6b604f;">Subtotal</td><td align="right" style="padding:6px 0;"><?= money((int) $order['subtotal_cents']) ?></td></tr>
  <?php if ($order['discount_cents'] > 0): ?>
  <tr><td style="padding:6px 0;color:#6b604f;">Discount (<?= e($order['coupon_code']) ?>)</td><td align="right" style="padding:6px 0;">−<?= money((int) $order['discount_cents']) ?></td></tr>
  <?php endif ?>
  <tr><td style="padding:6px 0;color:#6b604f;"><?= e($order['shipping_name']) ?></td><td align="right" style="padding:6px 0;"><?= money((int) $order['shipping_cents']) ?></td></tr>
  <tr><td style="padding:10px 0;font-weight:bold;border-top:1px solid #1a1612;">Total</td><td align="right" style="padding:10px 0;font-weight:bold;border-top:1px solid #1a1612;"><?= money((int) $order['total_cents']) ?></td></tr>
</table>

<?php if ($addr): ?>
<p style="margin:18px 0 0;color:#6b604f;font-size:14px;"><strong style="color:#1a1612;">Delivery</strong><br>
  <?= nl2br(e(implode("\n", array_filter([$addr['street'] ?? '', $addr['suburb'] ?? '', trim(($addr['city'] ?? '') . ' ' . ($addr['postcode'] ?? '')), $addr['province'] ?? '', isset($addr['locker']) ? 'PUDO locker: ' . $addr['locker'] : ''])))) ?></p>
<?php endif ?>

<p style="margin:24px 0 6px;"><a href="<?= e(order_url($order)) ?>" style="display:inline-block;background:#1a1612;color:#f8f4ec;text-decoration:none;padding:12px 22px;border-radius:999px;">View your order</a></p>
