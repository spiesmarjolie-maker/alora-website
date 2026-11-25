<h1>Delivery</h1>
<p class="muted">These are the options customers choose from at checkout.</p>
<div class="cards">
  <?php foreach (array_merge($methods, [null]) as $m): ?>
    <form class="panel stack <?= $m && !$m['active'] ? 'is-hidden' : '' ?>" method="post">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($m['id'] ?? 0) ?>">
      <h2><?= $m ? e($m['name']) : 'New delivery option' ?></h2>
      <div class="row3">
        <label>Name<input name="name" required value="<?= e($m['name'] ?? '') ?>"></label>
        <label>Price (R)<input name="price" inputmode="decimal" required value="<?= $m ? e(rand_input((int) $m['price_cents'])) : '' ?>"></label>
        <label>Order<input name="sort" type="number" value="<?= (int) ($m['sort'] ?? 9) ?>"></label>
      </div>
      <label>Description<input name="description" value="<?= e($m['description'] ?? '') ?>"></label>
      <label class="chk"><input type="checkbox" name="needs_address" value="1" <?= !$m || $m['needs_address'] ? 'checked' : '' ?>> Needs a street address (untick for locker or collection)</label>
      <label class="chk"><input type="checkbox" name="active" value="1" <?= !$m || $m['active'] ? 'checked' : '' ?>> Offer at checkout</label>
      <button class="btn"><?= $m ? 'Save' : 'Add option' ?></button>
    </form>
  <?php endforeach ?>
</div>
