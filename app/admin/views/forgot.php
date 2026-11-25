<form class="auth" method="post" action="/admin/forgot/">
  <?= csrf_field() ?>
  <p class="brand-lg">ALORA</p>
  <h1>Reset your password</h1>
  <p class="muted">We'll email you a link that works for one hour.</p>
  <label>Email<input name="email" type="email" autocomplete="email" required autofocus></label>
  <button class="btn btn--lg">Send link</button>
  <a href="/admin/login/">Back to sign in</a>
</form>
