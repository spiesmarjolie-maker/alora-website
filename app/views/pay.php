<section class="page-head page-head--center">
  <p class="eyebrow">Order <?= e($order['number']) ?></p>
  <h1 class="display-sm">Taking you to <em>Payfast…</em></h1>
  <p class="lead">Total <?= money((int) $order['total_cents']) ?>. If nothing happens, tap the button.</p>
  <form method="post" action="<?= e($action) ?>" data-autopay>
    <?php foreach ($fields as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>"><?php endforeach ?>
    <button class="pill pill--lg">Continue to payment</button>
  </form>
</section>
<script>document.querySelector('[data-autopay]').submit();</script>
