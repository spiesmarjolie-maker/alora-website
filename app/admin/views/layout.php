<?php
/** @var string $content @var string $title @var ?array $me */
$msg = flash();
$cur = $GLOBALS['section'] ?? '';
$toPack = $me ? (int) q("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn() : 0;
$nav = ['dashboard' => 'Dashboard', 'orders' => 'Orders', 'products' => 'Products', 'categories' => 'Categories',
    'coupons' => 'Discount codes', 'shipping' => 'Delivery'];
if (($me['role'] ?? '') === 'owner') {
    $nav += ['settings' => 'Settings', 'staff' => 'Staff'];
}
?><!doctype html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Alora admin</title>
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
<link rel="icon" href="/assets/img/icon.png">
</head>
<body class="<?= !empty($bare) ? 'bare' : '' ?>">
<?php if (empty($bare)): ?>
<header class="top">
  <a class="brand" href="/admin/">ALORA <small>admin</small></a>
  <nav class="side" aria-label="Admin">
    <?php foreach ($nav as $k => $label): ?>
      <a href="/admin/<?= $k === 'dashboard' ? '' : "$k/" ?>" <?= $cur === $k ? 'aria-current="page"' : '' ?>><?= $label ?><?= $k === 'orders' && $toPack ? " <b class=\"dot\">$toPack</b>" : '' ?></a>
    <?php endforeach ?>
  </nav>
  <div class="who">
    <a href="/" target="_blank" rel="noopener">View shop ↗</a>
    <span><?= e($me['name'] ?? '') ?></span>
    <form method="post" action="/admin/logout/"><?= csrf_field() ?><button>Sign out</button></form>
  </div>
</header>
<?php endif ?>
<main class="main">
  <?php if ($msg): ?><p class="flash" role="status"><?= e($msg) ?></p><?php endif ?>
  <?= $content ?>
</main>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
