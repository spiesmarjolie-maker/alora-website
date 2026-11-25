<?php
require_once __DIR__ . '/partials.php';
$f = $film;
$one = fn (array $list) => $list[0] ?? null;
// Film pieces use the transparent cut-out when there is one, so the product floats in the light.
$piece = function (?array $p, string $cls) {
    if (!$p) return '';
    $cut = $p['image'] && is_file(PUBLIC_DIR . "/media/{$p['image']}-cut-600.webp");
    return '<a class="piece ' . $cls . ($cut ? ' piece--cut' : '') . '" href="/product/' . e($p['slug']) . '/">'
        . img($cut ? $p['image'] . '-cut' : $p['image'], $p['name'], '(min-width: 900px) 22vw, 40vw') . '<span>' . e($p['name']) . '</span></a>';
};
?>
<!-- 1. The film: one pinned stage, scrubbed by scroll. Scenes hand over to each other. -->
<section class="film" data-film aria-label="Alora: where dreams meet light">
  <div class="film__stage">
    <?php if (is_file(PUBLIC_DIR . '/media/film/alora-film-1280.mp4')): ?>
    <!-- The Flow film: scrubbed by scroll in site.js (no autoplay). The poster is the still for reduced motion. -->
    <video class="film__video" data-film-video muted playsinline preload="none" aria-hidden="true" tabindex="-1"
      poster="/media/film/alora-film-poster.webp"
      data-src-wide="/media/film/alora-film-1280.mp4" data-src-narrow="/media/film/alora-film-960.mp4"></video>
    <?php endif ?>
    <div class="film__glow" aria-hidden="true"></div>
    <img class="film__logo" src="/assets/img/alora-logo.svg" alt="Alora" width="600" height="600" fetchpriority="high">
    <?= olive_branch('film__branch') ?>

    <div class="film__pieces">
      <?= $piece($f['jewellery'][0] ?? null, 'piece--a') ?>
      <?= $piece($f['jewellery'][1] ?? null, 'piece--b') ?>
      <?= $piece($one($f['nails']), 'piece--c') ?>
      <?= $piece($one($f['home']), 'piece--d') ?>
      <?= $piece($one($f['bath']), 'piece--e') ?>
      <?= $piece($one($f['book']), 'piece--f') ?>
      <?= $piece($one($f['gift']), 'piece--gift') ?>
    </div>

    <div class="film__ivory" aria-hidden="true"></div>
    <div class="film__copy">
      <div class="scene-text" data-scene="1">
        <p class="eyebrow">Gifts · Jewellery · Pamper<span class="only-wide"> · South Africa</span></p>
        <h1 class="display">Alora</h1>
        <p class="lead">Where dreams <em>meet light.</em></p>
        <p class="scroll-cue" aria-hidden="true">Scroll</p>
      </div>
      <div class="scene-text" data-scene="2">
        <p class="eyebrow">For women, by women</p>
        <h2 class="display">Made to be <em>given.</em></h2>
        <p class="lead">Jewellery, press-ons, pamper treats and pieces for the home, chosen with care and packed with love.</p>
      </div>
      <div class="scene-text" data-scene="3">
        <p class="eyebrow">Jewellery &amp; nails</p>
        <h2 class="display">Pretty pieces, <em>finished in gold.</em></h2>
        <p class="chips"><a href="/product-category/accessories/">Accessories &amp; Adornments</a><a href="/product-category/nails/">Press-Ons &amp; Nails</a></p>
      </div>
      <div class="scene-text" data-scene="4">
        <p class="eyebrow">Home, bath &amp; book</p>
        <h2 class="display">For the home, <em>the bath, the book.</em></h2>
        <p class="chips"><a href="/product-category/home-glow/">Home Glow</a><a href="/product-category/bath-body/">Beauty</a><a href="/product-category/stationary-lovers/">Stationery</a><a href="/product-category/christian-girlies/">Christian Girlies</a></p>
      </div>
      <div class="scene-text" data-scene="5">
        <p class="eyebrow">Gifts &amp; personalised</p>
        <h2 class="display">Yours, <em>or for someone special.</em></h2>
        <p class="actions"><a class="pill pill--gold" href="/product-category/ready-packed-gifts/">Ready packed gifts</a><a class="link" href="/personalised/">Make it personal →</a></p>
      </div>
    </div>
  </div>
</section>

<!-- 2. New in: cards settle onto the grid as the film lands. -->
<section class="sec sec--new" id="new">
  <header class="sec__head" data-reveal>
    <p class="eyebrow">Just in</p>
    <h2 class="display-sm">New in <em>the shop</em></h2>
    <a class="link" href="/shop/">Shop everything →</a>
  </header>
  <div class="grid"><?php foreach ($newIn as $p) echo card($p) ?></div>
</section>

<!-- 3. Categories: a sideways walk through the shop, driven by vertical scroll. -->
<section class="rail" data-rail aria-label="Shop by category">
  <div class="rail__pin">
    <header class="rail__head">
      <p class="eyebrow">Shop by category</p>
      <h2 class="display-sm">Find <em>your thing</em></h2>
    </header>
    <div class="rail__track" data-rail-track>
      <?php foreach ($cats as $c): ?>
        <a class="cat" href="/product-category/<?= e($c['slug']) ?>/">
          <?= img($c['image'], '', '(min-width: 900px) 28vw, 70vw', 'loading="lazy"') ?>
          <span class="cat__name"><?= e($c['name']) ?></span>
          <span class="cat__n"><?= (int) $c['n'] ?> pieces</span>
        </a>
      <?php endforeach ?>
    </div>
  </div>
</section>

<!-- 4. Gift sets: the picture stays put while the sets slide past. -->
<?php if ($gifts): ?>
<section class="sec sec--gifts">
  <div class="gifts">
    <div class="gifts__media" data-reveal><?= img($gifts[0]['image'], $gifts[0]['name'], '(min-width: 900px) 45vw, 92vw', 'loading="lazy"') ?></div>
    <div class="gifts__copy">
      <p class="eyebrow">Ready packed gifts</p>
      <h2 class="display-sm">Wrapped, ribboned, <em>ready to give.</em></h2>
      <p class="lead">Matching sets put together so you don't have to. Add a name or a verse and we'll personalise it.</p>
      <div class="gifts__list"><?php foreach ($gifts as $p) echo card($p, '(min-width: 900px) 18vw, 44vw') ?></div>
      <a class="pill" href="/product-category/ready-packed-gifts/">See all gift sets</a>
    </div>
  </div>
</section>
<?php endif ?>

<!-- 5. Personalised: dark band. -->
<section class="band">
  <div class="band__inner" data-reveal>
    <p class="eyebrow eyebrow--gold">Personalised treasures</p>
    <h2 class="display-sm">Something <em>uniquely yours.</em></h2>
    <p class="lead">Names, verses, teacher gifts, bridesmaid boxes, tumblers with your own design. Tell us your idea and we'll make it.</p>
    <p class="actions">
      <a class="pill pill--gold" href="<?= e(whatsapp_link('Hi Alora! I would like something personalised.')) ?>" target="_blank" rel="noopener">WhatsApp your idea</a>
      <a class="link link--light" href="/personalised/">See what we can do →</a>
    </p>
  </div>
</section>

<!-- 6. About, in one breath. -->
<section class="sec sec--about">
  <div class="about" data-reveal>
    <?= olive_branch('about__branch') ?>
    <p class="eyebrow">Our story</p>
    <h2 class="display-sm">For women, <em>by women.</em></h2>
    <p class="lead">Alora was born from a love for beautiful things, creativity and the little details that make life special. Established in 2025 as Jolie Femme, it grew into a dream of bringing beauty, pampering, thoughtful gifting and everyday luxuries together in one place.</p>
    <p class="actions"><a class="pill" href="/about/">Read our story</a><a class="link" href="/shop/">Start shopping →</a></p>
  </div>
</section>
