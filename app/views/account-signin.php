<?php
$val = fn ($k) => e((string) ($in[$k] ?? ''));
$err = fn ($k) => isset($errors[$k]) ? '<span class="field__err" role="alert">' . e($errors[$k]) . '</span>' : '';
?>
<section class="page-head"><p class="eyebrow">My account</p><h1 class="display-sm">Welcome <em>back</em></h1>
  <p class="lead">Sign in to see your orders and check out faster. You can also check out as a guest.</p></section>
<section class="sec sec--account">
  <div class="split">
    <form class="card-form" method="post" action="/account/login/">
      <?= csrf_field() ?>
      <h2 class="h-form">Sign in</h2>
      <?= $err('login') ?>
      <label class="field"><span>Email</span><input name="email" type="email" autocomplete="username" required value="<?= isset($errors['login']) ? $val('email') : '' ?>"></label>
      <label class="field"><span>Password</span><input name="password" type="password" autocomplete="current-password" required></label>
      <button class="pill pill--lg pill--block">Sign in</button>
      <a class="link" href="/account/forgot/">Forgot your password?</a>
    </form>

    <form class="card-form" method="post" action="/account/register/">
      <?= csrf_field() ?>
      <h2 class="h-form">New here? <em>Create an account</em></h2>
      <label class="field"><span>Full name</span><input name="name" autocomplete="name" required value="<?= $val('name') ?>"><?= $err('name') ?></label>
      <label class="field"><span>Email</span><input name="email" type="email" autocomplete="email" required value="<?= isset($errors['login']) ? '' : $val('email') ?>"><?= $err('email') ?></label>
      <label class="field"><span>Cell number (optional)</span><input name="phone" type="tel" autocomplete="tel" value="<?= $val('phone') ?>"></label>
      <label class="field"><span>Password (at least 10 characters)</span><input name="password" type="password" autocomplete="new-password" minlength="10" required><?= $err('password') ?></label>
      <label class="check"><input type="checkbox" name="agree" value="1"><span>I accept the <a href="/privacy/" target="_blank">privacy notice</a>.</span></label><?= $err('agree') ?>
      <button class="pill pill--lg pill--block">Create account</button>
    </form>
  </div>
</section>
