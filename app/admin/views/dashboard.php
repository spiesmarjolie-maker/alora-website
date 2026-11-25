<h1>Good <?= (int) date('G') < 12 ? 'morning' : ((int) date('G') < 17 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $me['name'])[0]) ?></h1>

<div class="stats">
  <a class="stat <?= $toPack ? 'stat--hot' : '' ?>" href="/admin/orders/?status=paid"><b><?= $toPack ?></b><span>to pack</span></a>
  <a class="stat" href="/admin/orders/?status=packed"><b><?= $toShip ?></b><span>packed, to ship</span></a>
  <div class="stat"><b><?= money($today) ?></b><span>sales today</span></div>
  <div class="stat"><b><?= money($month) ?></b><span><?= $monthOrders ?> orders this month</span></div>
  <div class="stat"><b><?= number_format($views) ?></b><span>page views, last 7 days</span></div>
</div>

<div class="cols">
  <section class="panel">
    <h2>Latest orders</h2>
    <?php if (!$recent): ?><p class="muted">No orders yet.</p><?php endif ?>
    <table class="tbl">
      <?php foreach ($recent as $o): ?>
        <tr><td><a href="/admin/orders/<?= $o['id'] ?>/"><?= e($o['number']) ?></a></td><td><?= e($o['name']) ?></td>
          <td><span class="pill-s pill-s--<?= e($o['status']) ?>"><?= ORDER_STATUSES[$o['status']] ?></span></td><td class="r"><?= money((int) $o['total_cents']) ?></td></tr>
      <?php endforeach ?>
    </table>
  </section>
  <section class="panel">
    <h2>Low stock <small>(<?= $lowCount ?>)</small></h2>
    <?php if (!$low): ?><p class="muted">Nothing running low.</p><?php endif ?>
    <table class="tbl">
      <?php foreach ($low as $p): ?>
        <tr><td><a href="/admin/products/<?= $p['id'] ?>/"><?= e($p['name']) ?></a></td><td class="r <?= $p['stock_qty'] ? '' : 'neg' ?>"><?= $p['stock_qty'] ? 'last one' : 'sold out' ?></td></tr>
      <?php endforeach ?>
    </table>
    <?php if ($lowCount > count($low)): ?><a href="/admin/products/?show=low">See all <?= $lowCount ?> →</a><?php endif ?>
    <?php if ($noPrice): ?>
      <h2 class="mt">Hidden because the price is R0</h2>
      <ul><?php foreach ($noPrice as $p): ?><li><a href="/admin/products/<?= $p['id'] ?>/"><?= e($p['name']) ?></a></li><?php endforeach ?></ul>
    <?php endif ?>
  </section>
</div>
