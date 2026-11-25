<section class="page-head"><h1 class="display-sm">Forgot your <em>password?</em></h1>
  <p class="lead">Enter your email and we'll send you a link to choose a new one.</p></section>
<section class="sec sec--account">
  <form class="card-form card-form--narrow" method="post" action="/account/forgot/">
    <?= csrf_field() ?>
    <label class="field"><span>Email</span><input name="email" type="email" autocomplete="email" required></label>
    <button class="pill pill--lg pill--block">Send reset link</button>
    <a class="link" href="/account/">Back to sign in</a>
  </form>
</section>
