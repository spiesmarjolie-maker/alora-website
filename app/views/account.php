<?php
$status = ['paid' => 'Paid', 'packed' => 'Packed', 'shipped' => 'On its way', 'completed' => 'Delivered', 'cancelled' => 'Cancelled'];
$a = fn ($k) => e((string) ($addr[$k] ?? ''));
?>
<section class="page-head"><p class="eyebrow">My account</p><h1 class="display-sm">Hello, <em><?= e(explode(' ', trim($c['name']))[0]) ?></em></h1></section>
<section class="sec sec--account">
  <div class="split">
    <div>
      <h2 class="h-form">Your orders</h2>
      <?php if (!$orders): ?>
        <p class="muted">No orders yet. <a href="/shop/">Find something pretty →</a></p>
      <?php else: ?>
        <ul class="order-list">
          <?php foreach ($orders as $o): ?>
            <li><a href="/order/<?= e($o['number']) ?>/">
              <span><strong><?= e($o['number']) ?></strong><small><?= e(date('j M Y', strtotime($o['created_at']))) ?></small></span>
              <span class="tag tag--<?= e($o['status']) ?>"><?= e($status[$o['status']] ?? $o['status']) ?></span>
              <span><?= money((int) $o['total_cents']) ?></span></a></li>
          <?php endforeach ?>
        </ul>
      <?php endif ?>
      <form method="post" action="/account/logout/" class="signout"><?= csrf_field() ?><button class="link">Sign out</button></form>
    </div>

    <form class="card-form" method="post" action="/account/details/">
      <?= csrf_field() ?>
      <h2 class="h-form">Your details</h2>
      <p class="muted small"><?= e($c['email']) ?></p>
      <label class="field"><span>Full name</span><input name="name" autocomplete="name" value="<?= e($c['name']) ?>"></label>
      <label class="field"><span>Cell number</span><input name="phone" type="tel" autocomplete="tel" value="<?= e($c['phone']) ?>"></label>
      <p class="muted small">Saved for faster checkout:</p>
      <label class="field"><span>PUDO locker (area or name)</span><input name="locker" value="<?= $a('locker') ?>"></label>
      <label class="field"><span>Street address</span><input name="street" autocomplete="street-address" value="<?= $a('street') ?>"></label>
      <label class="field"><span>Suburb</span><input name="suburb" value="<?= $a('suburb') ?>"></label>
      <div class="field-row">
        <label class="field"><span>Town / city</span><input name="city" value="<?= $a('city') ?>"></label>
        <label class="field field--sm"><span>Postal code</span><input name="postcode" inputmode="numeric" maxlength="4" value="<?= $a('postcode') ?>"></label>
      </div>
      <label class="field"><span>Province</span><select name="province"><option value="">Choose…</option>
        <?php foreach (PROVINCES as $p): ?><option <?= ($addr['province'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach ?></select></label>
      <details class="pw"><summary>Change password</summary>
        <label class="field"><span>Current password</span><input name="current_password" type="password" autocomplete="current-password"></label>
        <label class="field"><span>New password (10+ characters)</span><input name="new_password" type="password" autocomplete="new-password" minlength="10"></label>
      </details>
      <button class="pill pill--lg pill--block">Save</button>
    </form>
  </div>
</section>
