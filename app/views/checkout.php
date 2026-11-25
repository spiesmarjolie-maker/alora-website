<?php
$val = fn ($k) => e((string) ($in[$k] ?? ''));
$err = fn ($k) => isset($errors[$k]) ? '<span class="field__err" id="err-' . $k . '">' . e($errors[$k]) . '</span>' : '';
$inv = fn ($k) => isset($errors[$k]) ? 'aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
$selShip = (int) ($in['ship'] ?? $methods[0]['id']);
$selMethod = array_values(array_filter($methods, fn ($m) => (int) $m['id'] === $selShip))[0] ?? $methods[0];
?>
<section class="page-head"><p class="crumbs"><a href="/bag/">Bag</a> / Checkout</p><h1 class="display-sm">Almost <em>yours</em></h1></section>

<section class="sec sec--checkout">
  <?php if ($sandbox): ?><p class="notice">Test mode: payments go to the Payfast sandbox. No real money is taken.</p><?php endif ?>
  <?php if (!customer()): ?><p class="notice">Have an account? <a href="/account/">Sign in</a> for faster checkout, or just continue as a guest.</p><?php endif ?>
  <?php if ($errors): ?><div class="notice notice--err" role="alert">Please check the highlighted fields.<?= isset($errors['stock']) ? '<br>' . e($errors['stock']) : '' ?></div><?php endif ?>

  <form class="checkout" method="post" action="/checkout/" novalidate>
    <?= csrf_field() ?>
    <div class="checkout__form">
      <fieldset class="fs">
        <legend>Your details</legend>
        <label class="field"><span>Full name</span><input name="name" autocomplete="name" required value="<?= $val('name') ?>" <?= $inv('name') ?>><?= $err('name') ?></label>
        <label class="field"><span>Email</span><input name="email" type="email" autocomplete="email" required value="<?= $val('email') ?>" <?= $inv('email') ?>><?= $err('email') ?></label>
        <label class="field"><span>Cell number</span><input name="phone" type="tel" autocomplete="tel" inputmode="tel" required value="<?= $val('phone') ?>" <?= $inv('phone') ?>><?= $err('phone') ?></label>
      </fieldset>

      <fieldset class="fs">
        <legend>Delivery</legend>
        <?= $err('ship') ?>
        <?php foreach ($methods as $m): ?>
          <label class="choice">
            <input type="radio" name="ship" value="<?= (int) $m['id'] ?>" data-ship data-price="<?= (int) $m['price_cents'] ?>" data-address="<?= (int) $m['needs_address'] ?>" <?= (int) $m['id'] === $selShip ? 'checked' : '' ?>>
            <span class="choice__text"><strong><?= e($m['name']) ?></strong><small><?= e($m['description']) ?></small></span>
            <span class="choice__price"><?= money((int) $m['price_cents']) ?></span>
          </label>
        <?php endforeach ?>

        <div data-for-address="0" <?= $selMethod['needs_address'] ? 'hidden' : '' ?>>
          <label class="field"><span>Which PUDO locker suits you? (area or locker name)</span><input name="locker" value="<?= $val('locker') ?>" <?= $inv('locker') ?>><?= $err('locker') ?></label>
        </div>
        <div data-for-address="1" <?= $selMethod['needs_address'] ? '' : 'hidden' ?>>
          <label class="field"><span>Street address</span><input name="street" autocomplete="street-address" value="<?= $val('street') ?>" <?= $inv('street') ?>><?= $err('street') ?></label>
          <label class="field"><span>Suburb</span><input name="suburb" value="<?= $val('suburb') ?>"></label>
          <div class="field-row">
            <label class="field"><span>Town / city</span><input name="city" autocomplete="address-level2" value="<?= $val('city') ?>" <?= $inv('city') ?>><?= $err('city') ?></label>
            <label class="field field--sm"><span>Postal code</span><input name="postcode" autocomplete="postal-code" inputmode="numeric" maxlength="4" value="<?= $val('postcode') ?>" <?= $inv('postcode') ?>><?= $err('postcode') ?></label>
          </div>
          <label class="field"><span>Province</span>
            <select name="province" autocomplete="address-level1" <?= $inv('province') ?>>
              <option value="">Choose…</option>
              <?php foreach (PROVINCES as $p): ?><option <?= ($in['province'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach ?>
            </select><?= $err('province') ?></label>
        </div>
      </fieldset>

      <fieldset class="fs">
        <legend>Anything else?</legend>
        <label class="field"><span>Discount code (optional)</span><input name="coupon" autocapitalize="characters" value="<?= $val('coupon') ?>" <?= $inv('coupon') ?>><?= $err('coupon') ?></label>
        <label class="field"><span>Note or personalisation request (optional)</span><textarea name="note" rows="3"><?= $val('note') ?></textarea></label>
        <label class="check"><input type="checkbox" name="agree" value="1" <?= $inv('agree') ?>>
          <span>I accept the <a href="/terms/" target="_blank">terms &amp; returns</a> and the <a href="/privacy/" target="_blank">privacy notice</a>.</span></label>
        <?= $err('agree') ?>
      </fieldset>
    </div>

    <aside class="bag__sum checkout__sum">
      <ul class="sum-lines">
        <?php foreach ($lines as $l): ?><li><span><?= (int) $l['qty'] ?> × <?= e($l['name']) ?></span><span><?= money($l['line_cents']) ?></span></li><?php endforeach ?>
      </ul>
      <p class="row"><span>Subtotal</span><span><?= money($subtotal) ?></span></p>
      <p class="row"><span>Delivery</span><span data-ship-cost><?= money((int) $selMethod['price_cents']) ?></span></p>
      <p class="row row--total"><span>Total</span><strong data-total data-subtotal="<?= $subtotal ?>"><?= money($subtotal + (int) $selMethod['price_cents']) ?></strong></p>
      <p class="muted small">A discount code is taken off the total when you continue.</p>
      <button class="pill pill--lg pill--block">Pay securely with Payfast</button>
      <p class="muted small">Card, Instant EFT and more. Your card details go to Payfast only; we never see them.</p>
    </aside>
  </form>
</section>
