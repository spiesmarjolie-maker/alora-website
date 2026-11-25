<?php
$err = fn ($k) => isset($errors[$k]) ? '<span class="err">' . e($errors[$k]) . '</span>' : '';
$sale = $p && $p['regular_cents'] > $p['price_cents'];
?>
<p class="crumb"><a href="/admin/products/">← Products</a></p>
<div class="head-row">
  <h1><?= $id ? e($p['name']) : 'New product' ?><?= $id && !$p['active'] ? ' <span class="pill-s pill-s--cancelled">Hidden</span>' : '' ?></h1>
  <?php if ($id): ?>
    <div class="btns">
      <a class="btn btn--ghost" href="/product/<?= e($p['slug']) ?>/" target="_blank">View in shop ↗</a>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><button class="btn btn--ghost"><?= $p['active'] ? 'Hide from shop' : 'Show in shop' ?></button></form>
    </div>
  <?php endif ?>
</div>
<?php if ($id): ?><p class="muted small"><?= $sold ?> sold · last changed <?= e(date('j M Y', strtotime($p['updated_at']))) ?></p><?php endif ?>

<form class="cols cols--wide" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <section class="panel stack">
    <label>Name<input name="name" required value="<?= e($p['name'] ?? '') ?>"><?= $err('name') ?></label>
    <div class="row3">
      <label>Price (R)<input name="price" inputmode="decimal" required value="<?= e(rand_input($p['price_cents'] ?? null)) ?>"><?= $err('price') ?></label>
      <label>Was (R) <small class="muted">for a sale</small><input name="was" inputmode="decimal" value="<?= $sale ? e(rand_input((int) $p['regular_cents'])) : '' ?>"><?= $err('was') ?></label>
      <label>Stock <small class="muted">empty = always</small><input name="stock" inputmode="numeric" value="<?= e($p === null || $p['stock_qty'] === null ? '' : (string) $p['stock_qty']) ?>"><?= $err('stock') ?></label>
    </div>
    <label>Short description <small class="muted">(under the price)</small><textarea name="short_desc" rows="3"><?= e($p['short_desc'] ?? '') ?></textarea></label>
    <label>Details <small class="muted">(simple formatting: &lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;&lt;li&gt;)</small><textarea name="description" rows="6"><?= e($p['description'] ?? '') ?></textarea></label>
    <div class="row3">
      <label>SKU <small class="muted">optional</small><input name="sku" value="<?= e($p['sku'] ?? '') ?>"></label>
      <label>Web address <small class="muted">/product/…/</small><input name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="made from the name"></label>
      <label class="chk chk--pad"><input type="checkbox" name="featured" value="1" <?= !empty($p['featured']) ? 'checked' : '' ?>> Featured</label>
    </div>

    <fieldset>
      <legend>Gift bundle contents <small class="muted">(optional: selling this takes stock from these items)</small></legend>
      <div class="bundle" data-bundle>
        <?php foreach (array_merge($bundle, [['product_id' => 0, 'qty' => 1]]) as $b): ?>
          <div class="bundle__row">
            <select name="bundle_product[]" aria-label="Item in bundle"><option value="0">—</option>
              <?php foreach ($all as $o): if ((int) $o['id'] === $id) continue; ?><option value="<?= $o['id'] ?>" <?= (int) $o['id'] === (int) $b['product_id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option><?php endforeach ?>
            </select>
            <input name="bundle_qty[]" type="number" min="1" value="<?= (int) $b['qty'] ?>" aria-label="Quantity">
          </div>
        <?php endforeach ?>
      </div>
      <button type="button" class="btn btn--ghost btn--s" data-add-row>+ Another item</button>
    </fieldset>
  </section>

  <section class="panel stack">
    <fieldset>
      <legend>Categories</legend>
      <?php foreach ($cats as $c): ?>
        <label class="chk"><input type="checkbox" name="cats[]" value="<?= $c['id'] ?>" <?= in_array((int) $c['id'], array_map('intval', $mine), true) ? 'checked' : '' ?>> <?= e($c['name']) ?></label>
      <?php endforeach ?>
    </fieldset>
    <fieldset>
      <legend>Photos <small class="muted">(first one is the main photo)</small></legend>
      <div class="photos">
        <?php foreach ($images as $i => $im): ?>
          <figure>
            <img src="/media/<?= e($im['path']) ?>-600.webp" alt="">
            <figcaption>
              <?php if ($i): ?><button class="btn btn--s btn--ghost" name="do" value="image-main" formnovalidate onclick="this.form.image.value=<?= (int) $im['id'] ?>">Make main</button><?php else: ?><small>Main</small><?php endif ?>
              <button class="btn btn--s btn--danger" name="do" value="image-delete" formnovalidate onclick="this.form.image.value=<?= (int) $im['id'] ?>; return confirm('Remove this photo?')">Remove</button>
            </figcaption>
          </figure>
        <?php endforeach ?>
      </div>
      <input type="hidden" name="image" value="">
      <label>Add photos <small class="muted">JPG, PNG or WebP, up to 15 MB each</small><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
    </fieldset>
    <button class="btn btn--lg" name="do" value="save">Save product</button>
  </section>
</form>
