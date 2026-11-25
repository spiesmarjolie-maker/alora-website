<section class="page-head"><h1 class="display-sm">Choose a new <em>password</em></h1></section>
<section class="sec sec--account">
  <form class="card-form card-form--narrow" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label class="field"><span>New password (at least 10 characters)</span><input name="password" type="password" autocomplete="new-password" minlength="10" required>
      <?= isset($errors['password']) ? '<span class="field__err" role="alert">' . e($errors['password']) . '</span>' : '' ?></label>
    <button class="pill pill--lg pill--block">Save new password</button>
  </form>
</section>
