<h1>Categories</h1>
<p class="muted">The order number sets the order in the menu and on the homepage. Hidden categories disappear from the shop (their products stay).</p>
<div class="cards">
  <?php foreach (array_merge($cats, [null]) as $c): ?>
    <form class="panel stack" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($c['id'] ?? 0) ?>">
      <?php if ($c): ?>
        <div class="cat-head"><?= $c['image'] ? '<img src="/media/' . e($c['image']) . '-600.webp" alt="">' : '' ?><div><strong><?= e($c['name']) ?></strong><br><small class="muted"><?= (int) $c['n'] ?> products · /product-category/<?= e($c['slug']) ?>/</small></div></div>
      <?php else: ?><h2>New category</h2><?php endif ?>
      <label>Name<input name="name" required value="<?= e($c['name'] ?? '') ?>"></label>
      <label>Description <small class="muted">optional</small><textarea name="description" rows="2"><?= e($c['description'] ?? '') ?></textarea></label>
      <div class="row3">
        <label>Order<input name="sort" type="number" value="<?= (int) ($c['sort'] ?? 99) ?>"></label>
        <label class="chk chk--pad"><input type="checkbox" name="active" value="1" <?= !$c || $c['active'] ? 'checked' : '' ?>> Show in shop</label>
      </div>
      <label>Picture<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
      <button class="btn"><?= $c ? 'Save' : 'Add category' ?></button>
    </form>
  <?php endforeach ?>
</div>
