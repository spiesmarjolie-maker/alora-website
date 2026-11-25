<?php /** @var string $name @var string $url */ ?>
<h1 style="font-family:Georgia,'Times New Roman',serif;font-weight:normal;font-size:24px;margin:0 0 12px;">Reset your password</h1>
<p style="margin:0 0 16px;">Hi <?= e(explode(' ', trim($name ?: 'there'))[0]) ?>, someone (hopefully you) asked to reset your password. This link works once, for one hour.</p>
<p style="margin:0 0 18px;"><a href="<?= e($url) ?>" style="display:inline-block;background:#1a1612;color:#f8f4ec;text-decoration:none;padding:12px 22px;border-radius:999px;">Choose a new password</a></p>
<p style="margin:0;color:#6b604f;font-size:13px;">If you didn't ask for this, you can ignore this email; your password stays the same.</p>
