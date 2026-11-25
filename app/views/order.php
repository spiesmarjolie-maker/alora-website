<?php
require_once __DIR__ . '/partials.php';
$steps = ['paid' => 'Paid', 'packed' => 'Packed', 'shipped' => 'On its way', 'completed' => 'Delivered'];
$keys = array_keys($steps);
$at = array_search($order['status'], $keys, true);
$addr = json_decode((string) $order['address_json'], true) ?: [];
?>
<?php if ($order['status'] === 'pending'): ?><meta http-equiv="refresh" content="6"><?php endif ?>
<section class="page-head">
  <p class="eyebrow">Order <?= e($order['number']) ?></p>
  <?php if ($order['status'] === 'pending'): ?>
    <h1 class="display-sm">Confirming your <em>payment…</em></h1>
    <p class="lead">This usually takes a few seconds; this page updates by itself. If you cancelled the payment, <a href="/bag/">your bag is still here</a>.</p>
  <?php elseif ($order['status'] === 'cancelled'): ?>
    <h1 class="display-sm">This order was <em>cancelled</em></h1>
  <?php else: ?>
    <h1 class="display-sm">Thank you, <em><?= e(explode(' ', trim($order['name']))[0]) ?>!</em></h1>
    <p class="lead">Your order is confirmed. We've emailed a copy to <?= e($order['email']) ?>.</p>
  <?php endif ?>
</section>

<section class="sec sec--order">
  <?php if ($at !== false): ?>
    <ol class="track">
      <?php foreach ($steps as $k => $label): ?><li class="<?= array_search($k, $keys, true) <= $at ? 'is-done' : '' ?>"><?= $label ?></li><?php endforeach ?>
    </ol>
    <?php if ($order['tracking']): ?><p>Tracking number: <strong><?= e($order['tracking']) ?></strong></p><?php endif ?>
  <?php endif ?>

  <div class="bag">
    <ul class="bag__lines">
      <?php foreach ($items as $it): ?>
        <li class="line line--static">
          <span class="line__img"><?= img($it['image'], $it['name'], '96px') ?></span>
          <div class="line__info"><span class="line__name"><?= e($it['name']) ?></span><span class="line__unit"><?= (int) $it['qty'] ?> × <?= money((int) $it['unit_cents']) ?></span></div>
          <span class="line__total"><?= money($it['qty'] * $it['unit_cents']) ?></span>
        </li>
      <?php endforeach ?>
    </ul>
    <aside class="bag__sum">
      <p class="row"><span>Subtotal</span><span><?= money((int) $order['subtotal_cents']) ?></span></p>
      <?php if ($order['discount_cents'] > 0): ?><p class="row"><span>Discount (<?= e($order['coupon_code']) ?>)</span><span>−<?= money((int) $order['discount_cents']) ?></span></p><?php endif ?>
      <p class="row"><span><?= e($order['shipping_name']) ?></span><span><?= money((int) $order['shipping_cents']) ?></span></p>
      <p class="row row--total"><span>Total</span><strong><?= money((int) $order['total_cents']) ?></strong></p>
      <p class="muted small"><?= e(implode(', ', array_filter([$addr['locker'] ?? '', $addr['street'] ?? '', $addr['suburb'] ?? '', $addr['city'] ?? '', $addr['postcode'] ?? '']))) ?></p>
      <a class="link" href="<?= e(whatsapp_link("Hi Alora! A question about order {$order['number']}:")) ?>" target="_blank" rel="noopener">Question about this order? WhatsApp us →</a>
    </aside>
  </div>
</section>
