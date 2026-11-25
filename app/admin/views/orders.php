<h1>Orders</h1>
<nav class="tabs">
  <a href="/admin/orders/" <?= !$status ? 'aria-current="page"' : '' ?>>All</a>
  <?php foreach (ORDER_STATUSES as $k => $label): ?>
    <a href="/admin/orders/?status=<?= $k ?>" <?= $status === $k ? 'aria-current="page"' : '' ?>><?= $label ?> <small><?= (int) ($counts[$k] ?? 0) ?></small></a>
  <?php endforeach ?>
</nav>
<form class="bar" method="get"><input type="hidden" name="status" value="<?= e($status) ?>">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search order number, name, email or phone" aria-label="Search orders"><button class="btn">Search</button></form>

<?php if (!$orders): ?><p class="muted">No orders here.</p><?php else: ?>
<div class="tbl-wrap"><table class="tbl tbl--rows">
  <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Delivery</th><th>Status</th><th class="r">Total</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr data-href="/admin/orders/<?= $o['id'] ?>/">
      <td><a href="/admin/orders/<?= $o['id'] ?>/"><strong><?= e($o['number']) ?></strong></a></td>
      <td><?= e(date('j M, H:i', strtotime($o['paid_at'] ?? $o['created_at']))) ?></td>
      <td><?= e($o['name']) ?><br><small class="muted"><?= e($o['phone']) ?></small></td>
      <td><?= e($o['shipping_name']) ?></td>
      <td><span class="pill-s pill-s--<?= e($o['status']) ?>"><?= ORDER_STATUSES[$o['status']] ?></span></td>
      <td class="r"><?= money((int) $o['total_cents']) ?></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table></div>
<?php endif ?>
