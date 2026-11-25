<?php require_once __DIR__ . '/partials.php'; ?>
<section class="page-head">
  <h1 class="display-sm">Your <em>bag</em></h1>
</section>

<section class="sec sec--bag">
<?php if (!$lines): ?>
  <p class="empty">Your bag is empty. <a href="/shop/">Find something pretty →</a></p>
<?php else: ?>
  <div class="bag">
    <ul class="bag__lines">
      <?php foreach ($lines as $l): ?>
        <li class="line">
          <a class="line__img" href="/product/<?= e($l['slug']) ?>/"><?= img($l['image'], $l['name'], '96px') ?></a>
          <div class="line__info">
            <a class="line__name" href="/product/<?= e($l['slug']) ?>/"><?= e($l['name']) ?></a>
            <span class="line__unit"><?= money($l['price_cents']) ?> each</span>
          </div>
          <form method="post" action="/bag/" class="line__qty">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="set">
            <label><span class="sr">Quantity for <?= e($l['name']) ?></span>
              <input type="number" name="qty" value="<?= (int) $l['qty'] ?>" min="0" max="<?= $l['stock_qty'] ?? 99 ?>" onchange="this.form.submit()"></label>
            <noscript><button>Update</button></noscript>
          </form>
          <span class="line__total"><?= money($l['line_cents']) ?></span>
          <form method="post" action="/bag/"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="action" value="remove">
            <button class="line__remove" aria-label="Remove <?= e($l['name']) ?>">×</button></form>
        </li>
      <?php endforeach ?>
    </ul>

    <aside class="bag__sum">
      <p class="row"><span>Subtotal</span><strong><?= money($subtotal) ?></strong></p>
      <p class="muted">Delivery options at checkout:</p>
      <ul class="muted">
        <?php foreach ($shipping as $s): ?><li><?= e($s['name']) ?>: <?= money((int) $s['price_cents']) ?></li><?php endforeach ?>
      </ul>
      <a class="pill pill--lg pill--block" href="/checkout/">Checkout</a>
      <a class="link" href="<?= e(whatsapp_link("Hi Alora! I'd like to order:\n" . implode("\n", array_map(fn ($l) => "{$l['qty']} × {$l['name']}", $lines)))) ?>" target="_blank" rel="noopener">Or order on WhatsApp →</a>
    </aside>
  </div>
<?php endif ?>
</section>
