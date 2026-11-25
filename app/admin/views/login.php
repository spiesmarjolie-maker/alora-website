<form class="auth" method="post" action="/admin/login/">
  <?= csrf_field() ?>
  <p class="brand-lg">ALORA</p>
  <h1>Staff sign in</h1>
  <?php if ($err): ?><p class="err" role="alert"><?= e($err) ?></p><?php endif ?>
  <label>Email<input name="email" type="email" autocomplete="username" required autofocus></label>
  <label>Password<input name="password" type="password" autocomplete="current-password" required></label>
  <button class="btn btn--lg">Sign in</button>
  <a href="/admin/forgot/">Forgot password?</a>
</form>
