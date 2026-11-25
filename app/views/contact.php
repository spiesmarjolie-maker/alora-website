<?php require_once __DIR__ . '/partials.php'; ?>
<section class="page-head"><p class="eyebrow">Say hello</p><h1 class="display-sm">We'd love <em>to hear from you</em></h1>
  <p class="lead">Questions about an order, a custom gift idea, or just want to chat? WhatsApp is the quickest.</p></section>
<section class="sec">
  <div class="contact" data-reveal>
    <a class="contact__card" href="<?= e(whatsapp_link('Hi Alora!')) ?>" target="_blank" rel="noopener">
      <span class="eyebrow">WhatsApp</span><strong><?= e(setting('phone')) ?></strong><span class="link">Open a chat →</span></a>
    <a class="contact__card" href="tel:+<?= e(setting('whatsapp')) ?>">
      <span class="eyebrow">Call</span><strong><?= e(setting('phone')) ?></strong><span class="link">Tap to call →</span></a>
    <a class="contact__card" href="mailto:<?= e(setting('email')) ?>">
      <span class="eyebrow">Email</span><strong class="contact__mail"><?= e(setting('email')) ?></strong><span class="link">Send an email →</span></a>
    <?php if (setting('hours')): ?>
    <div class="contact__card"><span class="eyebrow">Opening hours</span><strong><?= e(setting('hours')) ?></strong><span class="muted">Messages after hours are answered the next working day.</span></div>
    <?php endif ?>
  </div>
  <?= olive_branch('contact__branch') ?>
</section>
