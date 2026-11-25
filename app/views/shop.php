<?php
require_once __DIR__ . '/partials.php';
$base = $cat ? '/product-category/' . $cat['slug'] . '/' : '/shop/';
$qs = fn (array $over) => $base . '?' . http_build_query(array_filter(array_merge(
    ['q' => $search, 'sort' => $sort === 'new' ? '' : $sort, 'instock' => $_GET['instock'] ?? ''], $over)));
?>
<section class="page-head">
  <p class="crumbs"><a href="/">Home</a> / <a href="/shop/">Shop</a><?= $cat ? ' / ' . e($cat['name']) : '' ?></p>
  <h1 class="display-sm"><?= $cat ? e($cat['name']) : ($search !== '' ? 'Results for <em>“' . e($search) . '”</em>' : 'Shop <em>everything</em>') ?></h1>
  <?php if ($cat && $cat['description']): ?><p class="lead"><?= e($cat['description']) ?></p><?php endif ?>
</section>

<section class="sec sec--shop">
  <nav class="chips chips--scroll" aria-label="Categories">
    <a href="/shop/" <?= !$cat ? 'aria-current="page"' : '' ?>>All</a>
    <?php foreach ($cats as $c): ?>
      <a href="/product-category/<?= e($c['slug']) ?>/" <?= $cat && $cat['id'] == $c['id'] ? 'aria-current="page"' : '' ?>><?= e($c['name']) ?></a>
    <?php endforeach ?>
  </nav>

  <form class="tools" method="get" action="<?= e($base) ?>">
    <label class="tools__search"><span class="sr">Search</span>
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search <?= $cat ? e($cat['name']) : 'all products' ?>…">
    </label>
    <label class="tools__check"><input type="checkbox" name="instock" value="1" <?= !empty($_GET['instock']) ? 'checked' : '' ?> onchange="this.form.submit()"> In stock only</label>
    <label class="tools__sort"><span class="sr">Sort</span>
      <select name="sort" onchange="this.form.submit()">
        <?php foreach (['new' => 'Newest first', 'low' => 'Price: low to high', 'high' => 'Price: high to low', 'az' => 'Name A–Z'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach ?>
      </select>
    </label>
    <noscript><button class="pill">Apply</button></noscript>
  </form>

  <p class="count"><?= $total ?> piece<?= $total === 1 ? '' : 's' ?></p>

  <?php if ($items): ?>
    <div class="grid"><?php foreach ($items as $p) echo card($p) ?></div>
  <?php else: ?>
    <p class="empty">Nothing found. <a href="/shop/">See everything</a> or <a href="<?= e(whatsapp_link('Hi Alora! Do you have ' . $search . '?')) ?>">ask us on WhatsApp</a>.</p>
  <?php endif ?>

  <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= e($qs(['page' => $i > 1 ? $i : ''])) ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
      <?php endfor ?>
    </nav>
  <?php endif ?>
</section>
