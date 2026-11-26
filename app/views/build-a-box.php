<?php require_once __DIR__ . '/partials.php';
$boxCents = (int) $box['price_cents'];
?>
<section class="page-head page-head--center">
  <p class="eyebrow">Made by you, packed by us</p>
  <h1 class="display-sm">Build a <em>Box</em></h1>
  <p class="lead">Pick anything from the shop and watch it go into your box. We pack it all with ribbon and love. The gift box is <?= money($boxCents) ?>, plus the pieces you choose.</p>
</section>

<form class="bab" method="post" action="/build-a-box/" data-bab data-box="<?= $boxCents ?>">
  <?= csrf_field() ?>

  <!-- The box: the chosen pieces drop in behind its front panel. -->
  <aside class="bab__side">
    <div class="bab__stage" aria-hidden="true">
      <div class="bab__lid"><svg class="bab__bow" viewBox="0 0 80 44"><path d="M40 24 C30 4 8 2 6 14 C4 26 26 28 40 24Z M40 24 C50 4 72 2 74 14 C76 26 54 28 40 24Z" fill="#c9a46a" stroke="#a77d43" stroke-width="1.5"/><path d="M38 24 L28 42 L34 41 L40 26 L46 41 L52 42 L42 24Z" fill="#b88f55"/><circle cx="40" cy="23" r="5" fill="#d4ad6c" stroke="#a77d43" stroke-width="1.5"/></svg></div>
      <div class="bab__back"></div>
      <div class="bab__items" data-bab-items></div>
      <div class="bab__front"><span class="bab__ribbon"></span><span class="bab__tag">ALORA</span></div>
    </div>
    <div class="bab__sum">
      <p class="row"><span>Gift box</span><span><?= money($boxCents) ?></span></p>
      <p class="row"><span data-bab-count>0 pieces</span><span data-bab-items-total>R0,00</span></p>
      <p class="row row--total"><span>Your box</span><strong data-bab-total><?= money($boxCents) ?></strong></p>
      <ul class="bab__list" data-bab-list></ul>
      <button class="pill pill--lg pill--block" data-bab-go disabled>Add my box to the bag</button>
      <p class="note-delivery"><strong>Delivery is not included</strong> and is added at checkout.</p>
    </div>
  </aside>

  <div class="bab__pick">
    <nav class="chips chips--scroll" aria-label="Filter by category">
      <button type="button" class="chip-btn" data-bab-cat="" aria-pressed="true">All</button>
      <?php foreach ($cats as $c): ?>
        <button type="button" class="chip-btn" data-bab-cat="<?= e($c['slug']) ?>" aria-pressed="false"><?= e($c['name']) ?></button>
      <?php endforeach ?>
    </nav>
    <div class="bab__grid">
      <?php foreach ($items as $p): ?>
        <label class="bab__item" data-cats="<?= e(implode(' ', $p['cats'])) ?>">
          <input type="checkbox" name="items[]" value="<?= (int) $p['id'] ?>" data-name="<?= e($p['name']) ?>"
            data-price="<?= (int) $p['price_cents'] ?>" data-img="<?= e($p['cut'] ?? ($p['image'] ? '/media/' . $p['image'] . '-600.webp' : '')) ?>" data-cut="<?= $p['cut'] ? 1 : 0 ?>">
          <span class="bab__img"><?= img($p['image'], $p['name'], '(min-width: 900px) 14vw, 30vw', 'loading="lazy"') ?></span>
          <span class="bab__name"><?= e($p['name']) ?></span>
          <span class="bab__price"><?= money((int) $p['price_cents']) ?></span>
          <span class="bab__add"><span class="bab__add-in">Add to box</span><span class="bab__add-on">In your box ✓</span></span>
        </label>
      <?php endforeach ?>
    </div>
  </div>

  <!-- Phones: the box total stays in reach at the bottom of the screen. -->
  <div class="bab__bar">
    <span class="bab__bar-txt"><strong data-bab-total-bar><?= money($boxCents) ?></strong> <span data-bab-count-bar>0 pieces</span></span>
    <button class="pill pill--gold" data-bab-go disabled>Add box to bag</button>
  </div>
</form>
