<form class="auth" method="post" action="<?= e($action) ?>">
  <?= csrf_field() ?>
  <p class="brand-lg">ALORA</p>
  <h1>Choose your password</h1>
  <?php if ($err): ?><p class="err" role="alert"><?= e($err) ?></p><?php endif ?>
  <label>New password (at least 10 characters)<input name="password" type="password" autocomplete="new-password" minlength="10" required autofocus></label>
  <button class="btn btn--lg">Save password</button>
</form>
