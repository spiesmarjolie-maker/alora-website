<?php
/** @var string $content @var string $title */
$shop = setting('shop_name', 'Alora');
$desc = $description ?? 'Alora: where dreams meet light.';
$count = bag_count();
$msg = flash();
?><!doctype html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<script>document.documentElement.classList.add('js')</script>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title === $shop ? $shop : "$title · $shop") ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#0e0c0a">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif ?>
<link rel="preload" href="/assets/fonts/cormorant-500.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/jost-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<link rel="icon" href="/assets/img/icon.png">
</head>
<body class="<?= !empty($dark) ? 'is-dark-top' : '' ?>">
<a class="skip" href="#main">Skip to content</a>
<div class="preview-strip">Website preview by NFC Software Solutions</div>

<header class="nav" data-nav>
  <a class="nav__brand" href="/" aria-label="<?= e($shop) ?> home">ALORA</a>
  <nav class="nav__links" aria-label="Main">
    <a href="/shop/">Shop</a>
    <a href="/product-category/ready-packed-gifts/">Gifts</a>
    <a href="/build-a-box/">Build a Box</a>
    <a href="/mystery-scoop/">Mystery Scoop</a>
    <a href="/teachers-box/">Teachers Box</a>
    <a href="/personalised/">Personalised</a>
    <a class="nav__wide" href="/about/">About</a>
    <a class="nav__wide" href="/contact/">Contact</a>
  </nav>
  <a class="nav__bag nav__acct" href="/account/" aria-label="<?= customer() ? 'My account' : 'Sign in' ?>">
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M4 21c1.6-4 4.6-6 8-6s6.4 2 8 6" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
  </a>
  <a class="nav__bag" href="/bag/" aria-label="Your bag, <?= $count ?> items">
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M5 8h14l-1 12H6z M9 8a3 3 0 0 1 6 0" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
    <span class="nav__count" data-bag-count <?= $count ? '' : 'hidden' ?>><?= $count ?></span>
  </a>
  <a class="pill" href="/shop/">Shop</a>
</header>

<?php if ($msg): ?><div class="toast is-on" role="status" data-toast><?= e($msg) ?></div><?php else: ?><div class="toast" role="status" data-toast></div><?php endif ?>

<main id="main"><?= $content ?></main>

<footer class="foot">
  <div class="foot__grid">
    <div>
      <p class="foot__brand">ALORA</p>
      <p class="foot__line">Where dreams meet light.<br>Chosen with love in South Africa.</p>
      <a class="pill pill--gold" href="<?= e(whatsapp_link('Hi Alora! I have a question.')) ?>" target="_blank" rel="noopener">WhatsApp us</a>
      <?= socials('socials--foot') ?>
    </div>
    <div>
      <p class="eyebrow">Shop</p>
      <ul>
        <?php foreach (array_slice(array_filter(categories(), fn ($c) => $c['n'] > 0), 0, 8) as $c): ?>
          <li><a href="/product-category/<?= e($c['slug']) ?>/"><?= e($c['name']) ?></a></li>
        <?php endforeach ?>
      </ul>
    </div>
    <div>
      <p class="eyebrow">Help</p>
      <ul>
        <li><a href="/build-a-box/">Build a Box</a></li>
        <li><a href="/mystery-scoop/">Mystery Scoop</a></li>
        <li><a href="/teachers-box/">Teachers Box</a></li>
        <li><a href="/about/">Our story</a></li>
        <li><a href="/contact/">Contact</a></li>
        <li><a href="/account/">My account</a></li>
        <li><a href="/terms/">Terms &amp; returns</a></li>
        <li><a href="/privacy/">Privacy (POPIA)</a></li>
        <li><a href="tel:+<?= e(setting('whatsapp')) ?>"><?= e(setting('phone')) ?></a></li>
      </ul>
    </div>
  </div>
  <div class="foot__base">
    <p>We only use your details to process your order and never sell them. <a href="/privacy/">Privacy notice (POPIA)</a>.</p>
    <p>© <?= date('Y') ?> Alora · <a href="/admin/">Staff login</a> · <span class="credit">Website by NFC Software Solutions</span></p>
  </div>
</footer>

<script>window.ALORA = { csrf: <?= json_encode(csrf_token()) ?> };</script>
<script src="<?= asset('vendor/gsap.min.js') ?>" defer></script>
<script src="<?= asset('vendor/ScrollTrigger.min.js') ?>" defer></script>
<script src="<?= asset('vendor/lenis.min.js') ?>" defer></script>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
