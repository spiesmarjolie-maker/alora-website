<h1>Discount codes</h1>
<p class="muted">Customers type the code at checkout. Codes are not case sensitive.</p>
<div class="cards">
  <?php foreach (array_merge([null], $coupons) as $c): ?>
    <form class="panel stack <?= $c && !$c['active'] ? 'is-hidden' : '' ?>" method="post">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) ($c['id'] ?? 0) ?>">
      <h2><?= $c ? e($c['code']) . ' <small class="muted">used ' . (int) $c['used_count'] . ($c['usage_limit'] ? ' of ' . (int) $c['usage_limit'] : '') . '×</small>' : 'New code' ?></h2>
      <div class="row3">
        <label>Code<input name="code" required value="<?= e($c['code'] ?? '') ?>" placeholder="e.g. WELCOME10" autocapitalize="characters"></label>
        <label>Type<select name="type"><option value="percent">% off</option><option value="fixed" <?= ($c['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Rand off</option></select></label>
        <label>Amount<input name="amount" inputmode="decimal" required value="<?= $c ? e($c['type'] === 'fixed' ? rand_input((int) $c['amount']) : (string) $c['amount']) : '' ?>"></label>
      </div>
      <div class="row3">
        <label>Minimum spend (R)<input name="min" inputmode="decimal" value="<?= $c && $c['min_spend_cents'] ? e(rand_input((int) $c['min_spend_cents'])) : '' ?>"></label>
        <label>Expires <small class="muted">optional</small><input name="expires" type="date" value="<?= e($c['expires_at'] ?? '') ?>"></label>
        <label>Max uses <small class="muted">optional</small><input name="limit" type="number" min="1" value="<?= e((string) ($c['usage_limit'] ?? '')) ?>"></label>
      </div>
      <label class="chk"><input type="checkbox" name="active" value="1" <?= !$c || $c['active'] ? 'checked' : '' ?>> Active</label>
      <button class="btn"><?= $c ? 'Save' : 'Create code' ?></button>
    </form>
  <?php endforeach ?>
</div>
