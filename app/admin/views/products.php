<div class="head-row"><h1>Products</h1><a class="btn" href="/admin/products/new/">+ Add product</a></div>
<form class="bar" method="get">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or SKU" aria-label="Search products">
  <select name="cat" aria-label="Category"><option value="0">All categories</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?></select>
  <select name="show" aria-label="Show"><?php foreach (['active' => 'In the shop', 'low' => 'Low / sold out', 'hidden' => 'Hidden', 'all' => 'Everything'] as $k => $v): ?>
    <option value="<?= $k ?>" <?= $show === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?></select>
  <button class="btn">Filter</button>
</form>
<p class="muted"><?= count($items) ?> products</p>
<div class="tbl-wrap"><table class="tbl tbl--rows">
  <thead><tr><th></th><th>Product</th><th class="r">Price</th><th class="r">Stock</th></tr></thead>
  <tbody>
  <?php foreach ($items as $p): ?>
    <tr data-href="/admin/products/<?= $p['id'] ?>/" class="<?= $p['active'] ? '' : 'is-hidden' ?>">
      <td class="thumb"><?= $p['image'] ? '<img src="/media/' . e($p['image']) . '-600.webp" alt="" loading="lazy">' : '' ?></td>
      <td><a href="/admin/products/<?= $p['id'] ?>/"><?= e($p['name']) ?></a><?= $p['is_bundle'] ? ' <small class="muted">bundle</small>' : '' ?><?= $p['active'] ? '' : ' <small class="muted">hidden</small>' ?></td>
      <td class="r"><?= $p['price_cents'] ? money((int) $p['price_cents']) : '<span class="neg">R0</span>' ?><?= $p['regular_cents'] > $p['price_cents'] ? '<br><small class="muted">sale</small>' : '' ?></td>
      <td class="r <?= $p['stock_qty'] !== null && $p['stock_qty'] <= 0 ? 'neg' : '' ?>"><?= $p['stock_qty'] === null ? '∞' : (int) $p['stock_qty'] ?></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table></div>
