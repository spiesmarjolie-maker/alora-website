<?php
$f = fn ($k, $label, $help = '', $type = 'text') => '<label>' . $label . ($help ? ' <small class="muted">' . $help . '</small>' : '')
    . '<input name="' . $k . '" type="' . $type . '" value="' . e($s[$k]) . '" autocomplete="off"></label>';
$secret = fn ($k, $label) => '<label>' . $label . ' <small class="muted">' . ($has[$k] ? 'saved; leave empty to keep it' : 'not set') . '</small>'
    . '<input name="' . $k . '" type="password" autocomplete="new-password" placeholder="' . ($has[$k] ? '••••••••' : '') . '"></label>'
    . ($has[$k] ? '<label class="chk"><input type="checkbox" name="clear_' . $k . '" value="1"> Remove the saved passphrase</label>' : '');
?>
<h1>Settings</h1>
<form class="cols cols--wide" method="post">
  <?= csrf_field() ?>
  <section class="panel stack">
    <h2>Shop</h2>
    <?= $f('shop_name', 'Shop name') ?>
    <?= $f('tagline', 'Tagline') ?>
    <div class="row3"><?= $f('phone', 'Phone (as shown)') ?><?= $f('whatsapp', 'WhatsApp number', 'international, e.g. 27817371752') ?></div>
    <?= $f('email', 'Contact email', 'shown on the site', 'email') ?>
    <?= $f('orders_to', 'Send new-order emails to', 'empty = contact email', 'email') ?>
    <?= $f('mail_from', 'Send emails from', 'a mailbox on your hosting, e.g. orders@yourdomain.co.za', 'email') ?>
    <?= $f('site_url', 'Website address', 'e.g. https://alora.co.za') ?>
  </section>

  <section class="panel stack">
    <h2>Social media</h2>
    <p class="muted small">Full links to your pages. Empty ones are simply not shown on the site.</p>
    <?= $f('instagram', 'Instagram', 'e.g. https://www.instagram.com/yourpage') ?>
    <?= $f('facebook', 'Facebook', 'e.g. https://www.facebook.com/yourpage') ?>
    <?= $f('tiktok', 'TikTok', 'e.g. https://www.tiktok.com/@yourpage') ?>
  </section>

  <section class="panel stack">
    <h2>Payfast</h2>
    <fieldset class="mode">
      <label class="chk"><input type="radio" name="payfast_mode" value="sandbox" <?= $mode !== 'live' ? 'checked' : '' ?>> <span><strong>Test mode (sandbox)</strong><br><small class="muted">No real money moves.</small></span></label>
      <label class="chk"><input type="radio" name="payfast_mode" value="live" <?= $mode === 'live' ? 'checked' : '' ?>> <span><strong>Live payments</strong><br><small class="muted">Customers pay for real.</small></span></label>
    </fieldset>
    <h3>Live account</h3>
    <p class="muted small">From your Payfast dashboard: Settings → Developer Settings. Set a passphrase there and enter the same one here.</p>
    <div class="row3"><?= $f('payfast_merchant_id', 'Merchant ID') ?><?= $f('payfast_merchant_key', 'Merchant key') ?></div>
    <?= $secret('payfast_passphrase', 'Passphrase') ?>
    <details>
      <summary>Your own sandbox account (optional)</summary>
      <p class="muted small">Leave empty to use Payfast's shared test merchant. Use your own (free at sandbox.payfast.co.za) to test signed payments before going live.</p>
      <div class="row3"><?= $f('payfast_sandbox_id', 'Sandbox merchant ID') ?><?= $f('payfast_sandbox_key', 'Sandbox merchant key') ?></div>
      <?= $secret('payfast_sandbox_passphrase', 'Sandbox passphrase') ?>
    </details>
    <button class="btn btn--lg">Save settings</button>
  </section>
</form>
