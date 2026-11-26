<?php require_once __DIR__ . '/partials.php';
$each = (int) $scoop['price_cents'];
?>
<section class="page-head page-head--center">
  <p class="eyebrow">Surprise, surprise</p>
  <h1 class="display-sm">Mystery <em>Scoop</em></h1>
  <p class="lead">A scoop of surprise goodies, hand-picked and packed by us. Every scoop is different, and every one is a little treat. <?= money($each) ?> per scoop.</p>
</section>

<section class="sec sec--scoop">
  <form class="scoop" method="post" action="/bag/" data-scoop data-each="<?= $each ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $scoop['id'] ?>">
    <input type="hidden" name="action" value="add">

    <div class="scoop__art" aria-hidden="true">
      <?php if ($scoop['image']): ?><?= img($scoop['image'], '', '(min-width: 900px) 40vw, 80vw') ?>
      <?php else: ?>
        <!-- A gold scoop heaped with little surprises (until a photo is added in admin). -->
        <svg class="scoop__svg" viewBox="0 0 320 260">
          <defs>
            <linearGradient id="sc-gold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f3dcaa"/><stop offset=".5" stop-color="#d4ad6c"/><stop offset="1" stop-color="#a77d43"/></linearGradient>
          </defs>
          <g class="scoop__goodies">
            <circle cx="112" cy="118" r="26" fill="#f2c6cf"/><circle cx="160" cy="98" r="30" fill="#e9d6f2"/>
            <circle cx="206" cy="120" r="24" fill="#cfe8df"/><circle cx="140" cy="128" r="20" fill="#f6e2b5"/>
            <circle cx="184" cy="132" r="18" fill="#f4cdb9"/>
            <path d="M160 70 l5 11 12 2-9 8 2 12-10-6-10 6 2-12-9-8 12-2z" fill="#c9a46a"/>
            <path d="M104 100 c-6-10 6-18 12-8 c6-10 18-2 12 8 l-12 12z" fill="#e58fa3"/>
          </g>
          <path d="M70 138 h180 c0 52-40 86-90 86 s-90-34-90-86z" fill="url(#sc-gold)"/>
          <rect x="62" y="130" width="196" height="14" rx="7" fill="#b88f55"/>
          <path d="M250 150 l52 -18 c8-3 12 8 4 12 l-52 22z" fill="url(#sc-gold)"/>
        </svg>
      <?php endif ?>
    </div>

    <div class="scoop__pick">
      <fieldset>
        <legend class="eyebrow">How many scoops?</legend>
        <div class="scoop__options">
          <?php for ($n = 1; $n <= 7; $n++): ?>
            <label class="scoop__opt">
              <input type="radio" name="qty" value="<?= $n ?>" <?= $n === 1 ? 'checked' : '' ?>>
              <span class="scoop__n"><?= $n ?></span>
              <span class="scoop__label"><?= $n === 1 ? 'scoop' : 'scoops' ?></span>
              <span class="scoop__price"><?= money($each * $n) ?></span>
            </label>
          <?php endfor ?>
        </div>
      </fieldset>
      <p class="scoop__total"><span>Total</span><strong data-scoop-total><?= money($each) ?></strong></p>
      <button class="pill pill--lg pill--block">Add to bag</button>
      <p class="note-delivery"><strong>Delivery is not included</strong> and is added at checkout: <?= delivery_prices() ?>.</p>
      <ul class="pd__notes">
        <li>Each scoop is a surprise: no two are the same</li>
        <li>Lovingly packed by hand</li>
        <li>Questions? <a href="<?= e(whatsapp_link('Hi Alora! I have a question about the Mystery Scoops.')) ?>" target="_blank" rel="noopener">Ask us on WhatsApp</a></li>
      </ul>
    </div>
  </form>
</section>
