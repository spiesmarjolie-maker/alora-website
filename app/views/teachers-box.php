<?php require_once __DIR__ . '/partials.php';
$boxCents = (int) $box['price_cents'];
$f = $form + ['name' => '', 'line' => '', 'colour' => 'blue', 'theme' => 'florals', 'wish' => ''];
$colours = TB_COLOURS;   // defined in app/pages/teachers-box.php
$themes = TB_THEMES;
$includes = ['Personalised stationery box', '8 neon gel ink pens', 'Highlighter', '2 pens', 'Pencil',
    'Pencil sharpener (with container)', 'Page flags / sticky tabs', 'Paper clips'];
?>
<section class="page-head page-head--center">
  <p class="eyebrow">Unique designs · your name · your grade · your subject · your style</p>
  <h1 class="display-sm">Teachers <em>Box</em></h1>
  <p class="lead">A personalised plastic stationery box, filled and ready for class. Add your name and design, or upload your own picture, then add extra stationery. <?= money($boxCents) ?> per set.</p>
</section>

<form class="tb" method="post" action="/teachers-box/" enctype="multipart/form-data" data-tb data-box="<?= $boxCents ?>">
  <?= csrf_field() ?>

  <!-- Live preview of the box lid: colour, design or own picture, name and line 2. -->
  <aside class="tb__side">
    <div class="tb__box" data-tb-box data-colour="<?= e($f['colour']) ?>" data-theme="<?= e($f['theme']) ?>" aria-hidden="true">
      <div class="tb__lid">
        <div class="tb__label">
          <img class="tb__pic" data-tb-pic alt="" hidden>
          <span class="tb__art"></span>
          <span class="tb__name" data-tb-name><?= e($f['name'] ?: 'Ms Spies') ?></span>
          <span class="tb__line" data-tb-line><?= e($f['line'] ?: 'Grade 1 Teacher') ?></span>
        </div>
      </div>
    </div>
    <p class="tb__hint">A preview only: we design your box by hand and WhatsApp you if anything needs checking.</p>

    <div class="bab__sum">
      <p class="row"><span>Teachers Box set</span><span><?= money($boxCents) ?></span></p>
      <p class="row"><span data-tb-count>No extras</span><span data-tb-extras>R0,00</span></p>
      <p class="row row--total"><span>Your set</span><strong data-tb-total><?= money($boxCents) ?></strong></p>
      <button class="pill pill--lg pill--block">Add my Teachers Box to the bag</button>
      <p class="note-delivery"><strong>Delivery is not included</strong> and is added at checkout.</p>
    </div>
  </aside>

  <div class="tb__main">
    <fieldset class="tb__step">
      <legend class="tb__h"><span>1</span> Your name on the box</legend>
      <label class="field"><span>Name (for example "Ms Spies" or "Juffrou Rulyn")</span>
        <input name="box_name" maxlength="30" required value="<?= e($f['name']) ?>" data-tb-in="name" autocomplete="off"></label>
      <label class="field"><span>Second line: grade or subject (optional)</span>
        <input name="box_line" maxlength="50" value="<?= e($f['line']) ?>" data-tb-in="line" placeholder="Grade 1 Teacher" autocomplete="off"></label>
    </fieldset>

    <fieldset class="tb__step">
      <legend class="tb__h"><span>2</span> Box colour</legend>
      <div class="tb__swatches">
        <?php foreach ($colours as $k => $label): ?>
          <label class="tb__swatch tb__swatch--<?= $k ?>"><input type="radio" name="colour" value="<?= $k ?>" <?= $f['colour'] === $k ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
        <?php endforeach ?>
      </div>
    </fieldset>

    <fieldset class="tb__step">
      <legend class="tb__h"><span>3</span> Design</legend>
      <div class="tb__themes">
        <?php foreach ($themes as $k => $label): ?>
          <label class="tb__theme tb__theme--<?= $k ?>"><input type="radio" name="theme" value="<?= $k ?>" <?= $f['theme'] === $k ? 'checked' : '' ?>><span class="tb__theme-art"></span><span><?= e($label) ?></span></label>
        <?php endforeach ?>
      </div>
      <label class="field tb__upload"><span>Your own picture for the box (a logo, your class photo, a drawing). JPG, PNG or iPhone photo, up to 10 MB.</span>
        <input type="file" name="picture" accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic" data-tb-file></label>
      <label class="field"><span>Anything else about your design? (font, favourite colours, subject icons)</span>
        <textarea name="wish" rows="2" maxlength="300"><?= e($f['wish']) ?></textarea></label>
    </fieldset>

    <div class="tb__step">
      <h2 class="tb__h"><span>4</span> In every set</h2>
      <ul class="tb__includes"><?php foreach ($includes as $i): ?><li><?= e($i) ?></li><?php endforeach ?></ul>
    </div>

    <?php if ($addons): ?>
    <fieldset class="tb__step">
      <legend class="tb__h"><span>5</span> Optional add-ons</legend>
      <div class="tb__addons">
        <?php foreach ($addons as $a): ?>
          <label class="tb__addon"><input type="checkbox" name="addons[]" value="<?= (int) $a['id'] ?>" data-price="<?= (int) $a['price_cents'] ?>">
            <span><?= e(preg_replace('/^Teachers Box add-on: /', '', $a['name'])) ?></span><strong><?= money((int) $a['price_cents']) ?></strong></label>
        <?php endforeach ?>
      </div>
    </fieldset>
    <?php endif ?>

    <?php if ($extras): ?>
    <fieldset class="tb__step">
      <legend class="tb__h"><span>6</span> Add stationery from the shop</legend>
      <p class="muted">We pack these into your box too.</p>
      <div class="bab__grid">
        <?php foreach ($extras as $p): ?>
          <label class="bab__item">
            <input type="checkbox" name="extras[]" value="<?= (int) $p['id'] ?>" data-price="<?= (int) $p['price_cents'] ?>">
            <span class="bab__img"><?= img($p['image'], $p['name'], '(min-width: 900px) 14vw, 30vw', 'loading="lazy"') ?></span>
            <span class="bab__name"><?= e($p['name']) ?></span>
            <span class="bab__price"><?= money((int) $p['price_cents']) ?></span>
            <span class="bab__add"><span class="bab__add-in">Add to box</span><span class="bab__add-on">In your box ✓</span></span>
          </label>
        <?php endforeach ?>
      </div>
    </fieldset>
    <?php endif ?>
  </div>

  <div class="bab__bar">
    <span class="bab__bar-txt"><strong data-tb-total-bar><?= money($boxCents) ?></strong> Teachers Box</span>
    <button class="pill pill--gold">Add to bag</button>
  </div>
</form>
