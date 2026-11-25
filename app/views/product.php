<?php
require_once __DIR__ . '/partials.php';
$left = $p['stock_qty'] === null ? null : (int) $p['stock_qty'];
$sale = $p['regular_cents'] > $p['price_cents'];
$buyable = $p['price_cents'] > 0 && $left !== 0;
$schema = [
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'],
    'image' => array_map(fn ($i) => "/media/{$i['path']}-1200.webp", $images),
    'offers' => ['@type' => 'Offer', 'priceCurrency' => 'ZAR', 'price' => number_format($p['price_cents'] / 100, 2, '.', ''),
        'availability' => $left === 0 ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock'],
];
?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

<section class="pd">
  <p class="crumbs"><a href="/">Home</a> / <?php if ($cats): ?><a href="/product-category/<?= e($cats[0]['slug']) ?>/"><?= e($cats[0]['name']) ?></a> / <?php endif ?><?= e($p['name']) ?></p>
  <div class="pd__grid">
    <div class="pd__gallery" data-gallery>
      <div class="pd__main" data-reveal><?= $images ? img($images[0]['path'], $images[0]['alt'], '(min-width: 900px) 50vw, 100vw', 'data-main fetchpriority="high"') : img(null, '') ?></div>
      <?php if (count($images) > 1): ?>
        <div class="pd__thumbs">
          <?php foreach ($images as $i => $im): ?>
            <button type="button" data-thumb="<?= e($im['path']) ?>" aria-label="Show photo <?= $i + 1 ?>" <?= $i ? '' : 'aria-current="true"' ?>><?= img($im['path'], '', '80px', 'loading="lazy"') ?></button>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>

    <div class="pd__info">
      <?php if ($cats): ?><p class="eyebrow"><?= e(implode(' · ', array_column($cats, 'name'))) ?></p><?php endif ?>
      <h1 class="display-sm"><?= e($p['name']) ?></h1>
      <p class="pd__price"><?php if ($p['price_cents'] > 0): ?><?= $sale ? '<del>' . money($p['regular_cents']) . '</del> ' : '' ?><?= money($p['price_cents']) ?><?php else: ?>Price on request<?php endif ?></p>
      <p class="pd__stock <?= $left === 0 ? 'is-out' : '' ?>"><?= $left === 0 ? 'Sold out' : ($left === 1 ? 'Last one!' : ($left ? "$left in stock" : 'In stock')) ?></p>

      <?php if ($p['short_desc']): ?><div class="prose"><?= $p['short_desc'] ?></div><?php endif ?>

      <?php if ($buyable): ?>
        <form class="pd__buy" method="post" action="/bag/" data-add-form>
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <label class="qty"><span class="sr">Quantity</span>
            <input type="number" name="qty" value="1" min="1" max="<?= $left ?? 99 ?>" inputmode="numeric">
          </label>
          <button class="pill pill--lg">Add to bag</button>
        </form>
      <?php endif ?>
      <a class="link" href="<?= e(whatsapp_link("Hi Alora! I'm interested in: {$p['name']}")) ?>" target="_blank" rel="noopener">Ask about this on WhatsApp →</a>

      <ul class="pd__notes">
        <li>Lovingly packed by hand</li>
        <li>PUDO locker or door-to-door delivery across South Africa</li>
        <li>Want it personalised? <a href="<?= e(whatsapp_link("Hi Alora! Can {$p['name']} be personalised?")) ?>">Just ask</a></li>
      </ul>

      <?php if ($p['description']): ?><details class="pd__more" open><summary>Details</summary><div class="prose"><?= $p['description'] ?></div></details><?php endif ?>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section class="sec">
  <header class="sec__head" data-reveal><p class="eyebrow">More like this</p><h2 class="display-sm">You might <em>also love</em></h2></header>
  <div class="grid"><?php foreach ($related as $r) echo card($r) ?></div>
</section>
<?php endif ?>
