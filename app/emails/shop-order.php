<?php
/** Email to the shop for a new paid order. @var array $order @var array $items @var array $lowStock */
$addr = json_decode((string) $order['address_json'], true) ?: [];
?>
<h1 style="font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:24px;margin:0 0 12px;">New order <?= e($order['number']) ?> 🎉</h1>
<p style="margin:0 0 16px;"><strong><?= money((int) $order['total_cents']) ?></strong> paid via Payfast.</p>
<?php if ($lowStock): ?>
  <p style="margin:0 0 16px;padding:12px 14px;background:#fbeaea;border-radius:8px;">⚠ Stock ran short on: <?= e(implode(', ', $lowStock)) ?>. Please check before packing.</p>
<?php endif ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;margin-bottom:16px;">
  <?php foreach ($items as $it): ?>
  <tr><td style="padding:7px 0;border-bottom:1px solid #efe7d8;"><?= (int) $it['qty'] ?> × <?= e($it['name']) ?></td>
      <td align="right" style="padding:7px 0;border-bottom:1px solid #efe7d8;"><?= money($it['qty'] * $it['unit_cents']) ?></td></tr>
  <?php endforeach ?>
</table>
<p style="margin:0 0 6px;"><strong>Customer:</strong> <?= e($order['name']) ?>, <?= e($order['phone']) ?>, <?= e($order['email']) ?></p>
<p style="margin:0 0 6px;"><strong>Delivery:</strong> <?= e($order['shipping_name']) ?></p>
<p style="margin:0 0 6px;color:#6b604f;"><?= e(implode(', ', array_filter([$addr['locker'] ?? '', $addr['street'] ?? '', $addr['suburb'] ?? '', $addr['city'] ?? '', $addr['postcode'] ?? '', $addr['province'] ?? '']))) ?></p>
<?php if ($order['customer_note']): ?><p style="margin:12px 0 0;"><strong>Note:</strong> <?= nl2br(e($order['customer_note'])) ?></p><?php endif ?>
<p style="margin:20px 0 0;"><a href="<?= e(site_url('/admin/orders/' . (int) $order['id'] . '/')) ?>" style="display:inline-block;background:#1a1612;color:#f8f4ec;text-decoration:none;padding:12px 22px;border-radius:999px;">Open in admin</a></p>
