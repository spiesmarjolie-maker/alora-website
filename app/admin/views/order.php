<?php
$next = ['paid' => 'packed', 'packed' => 'shipped', 'shipped' => 'completed'][$order['status']] ?? null;
$wa = 'https://wa.me/' . preg_replace('/^0/', '27', preg_replace('/\D/', '', $order['phone']));
?>
<p class="crumb"><a href="/admin/orders/">← Orders</a></p>
<div class="head-row">
  <h1><?= e($order['number']) ?> <span class="pill-s pill-s--<?= e($order['status']) ?>"><?= ORDER_STATUSES[$order['status']] ?></span></h1>
  <div class="btns">
    <a class="btn btn--ghost" href="/admin/orders/<?= $order['id'] ?>/packing/" target="_blank">Packing slip</a>
    <a class="btn btn--ghost" href="/admin/orders/<?= $order['id'] ?>/invoice/" target="_blank">Invoice</a>
  </div>
</div>

<div class="cols">
  <section class="panel">
    <h2>Items</h2>
    <table class="tbl">
      <?php foreach ($items as $it): ?>
        <tr><td class="thumb"><?= $it['image'] ? '<img src="/media/' . e($it['image']) . '-600.webp" alt="">' : '' ?></td>
          <td><?= $it['slug'] ? '<a href="/admin/products/' . (int) $it['product_id'] . '/">' . e($it['name']) . '</a>' : e($it['name']) ?></td>
          <td class="r"><?= (int) $it['qty'] ?> × <?= money((int) $it['unit_cents']) ?></td><td class="r"><?= money($it['qty'] * $it['unit_cents']) ?></td></tr>
      <?php endforeach ?>
      <tr><td></td><td class="muted">Subtotal</td><td></td><td class="r"><?= money((int) $order['subtotal_cents']) ?></td></tr>
      <?php if ($order['discount_cents']): ?><tr><td></td><td class="muted">Discount <?= e($order['coupon_code']) ?></td><td></td><td class="r">−<?= money((int) $order['discount_cents']) ?></td></tr><?php endif ?>
      <tr><td></td><td class="muted"><?= e($order['shipping_name']) ?></td><td></td><td class="r"><?= money((int) $order['shipping_cents']) ?></td></tr>
      <tr class="total"><td></td><td>Total</td><td></td><td class="r"><?= money((int) $order['total_cents']) ?></td></tr>
    </table>
    <?php if ($order['customer_note']): ?><h3>Customer note</h3><p class="note"><?= nl2br(e($order['customer_note'])) ?></p><?php endif ?>

    <h2 class="mt">History</h2>
    <ol class="timeline">
      <?php foreach ($events as $ev): ?>
        <li><b><?= ORDER_STATUSES[$ev['status']] ?? e($ev['status']) ?></b> · <?= e(date('j M Y, H:i', strtotime($ev['created_at']))) ?><?= $ev['by_name'] ? ' · ' . e($ev['by_name']) : '' ?><?= $ev['emailed'] ? ' · customer emailed' : '' ?>
          <?php if ($ev['note']): ?><br><span class="muted"><?= e($ev['note']) ?></span><?php endif ?></li>
      <?php endforeach ?>
    </ol>
  </section>

  <section class="panel">
    <h2>Customer</h2>
    <p><strong><?= e($order['name']) ?></strong><br>
      <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a><br>
      <a href="tel:<?= e($order['phone']) ?>"><?= e($order['phone']) ?></a> · <a href="<?= e($wa) ?>" target="_blank" rel="noopener">WhatsApp</a></p>
    <h3><?= e($order['shipping_name']) ?></h3>
    <p><?= nl2br(e(implode("\n", array_filter([isset($addr['locker']) ? 'Locker: ' . $addr['locker'] : '', $addr['street'] ?? '', $addr['suburb'] ?? '',
        trim(($addr['city'] ?? '') . ' ' . ($addr['postcode'] ?? '')), $addr['province'] ?? ''])))) ?></p>
    <?php if ($order['tracking']): ?><p>Tracking: <strong><?= e($order['tracking']) ?></strong></p><?php endif ?>
    <?php if ($order['payfast_id']): ?><p class="muted small">Payfast payment <?= e($order['payfast_id']) ?></p><?php endif ?>

    <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
    <form class="stack box" method="post">
      <?= csrf_field() ?>
      <h2>Update order</h2>
      <?php if ($order['status'] === 'pending'): ?><p class="muted small">Not paid yet. It will move to "Paid, to pack" by itself when Payfast confirms.</p><?php endif ?>
      <label>New status
        <select name="status" data-status>
          <?php foreach (['packed' => 'Packed', 'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $l): ?>
            <option value="<?= $k ?>" <?= $k === $next ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach ?>
          <option value="note">Just add a note</option>
        </select></label>
      <label data-only="shipped">PUDO tracking number<input name="tracking" value="<?= e($order['tracking']) ?>"></label>
      <label class="chk" data-only="shipped"><input type="checkbox" name="no_tracking" value="1"> No tracking number</label>
      <label class="chk" data-only="cancelled"><input type="checkbox" name="restock" value="1" checked> Put the items back in stock</label>
      <label>Note <small class="muted">(shown to the customer if emailed)</small><textarea name="note" rows="2"></textarea></label>
      <?php if ($order['status'] !== 'pending'): ?><label class="chk"><input type="checkbox" name="email_customer" value="1" checked> Email the customer</label><?php endif ?>
      <button class="btn btn--lg">Save</button>
    </form>
    <?php endif ?>
  </section>
</div>
