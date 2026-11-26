<?php
/** Shop settings and Payfast keys (owner only). Secret fields are write-only: leaving them empty keeps the saved value. */
const PLAIN_SETTINGS = ['shop_name', 'tagline', 'phone', 'whatsapp', 'email', 'orders_to', 'mail_from', 'site_url',
    'payfast_merchant_id', 'payfast_merchant_key', 'payfast_sandbox_id', 'payfast_sandbox_key',
    'instagram', 'facebook', 'tiktok'];
const SECRET_SETTINGS = ['payfast_passphrase', 'payfast_sandbox_passphrase'];

if ($method === 'POST') {
    $in = fn ($k) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, 300);
    $bad = [];
    foreach (['email', 'orders_to', 'mail_from'] as $k) {
        if ($in($k) !== '' && !filter_var($in($k), FILTER_VALIDATE_EMAIL)) $bad[] = "“$k” is not a valid email";
    }
    foreach (['instagram', 'facebook', 'tiktok'] as $k) {
        if ($in($k) !== '' && !preg_match('#^https://(www\.)?(instagram\.com|facebook\.com|fb\.com|tiktok\.com)/\S+$#i', $in($k))) $bad[] = "the $k link must be a full https:// link to your page";
    }
    if ($in('site_url') !== '' && !preg_match('#^https://[^/\s]+$#', rtrim($in('site_url'), '/'))) $bad[] = 'the web address must look like https://example.co.za';
    $mode = ($_POST['payfast_mode'] ?? '') === 'live' ? 'live' : 'sandbox';
    if ($mode === 'live' && ($in('payfast_merchant_id') === '' || $in('payfast_merchant_key') === '')) {
        $bad[] = 'live payments need your Payfast merchant ID and key';
    }
    if ($bad) {
        flash('Not saved: ' . implode('; ', $bad) . '.');
        redirect('/admin/settings/');
    }
    foreach (PLAIN_SETTINGS as $k) {
        set_setting($k, $k === 'site_url' ? rtrim($in($k), '/') : ($k === 'whatsapp' ? preg_replace('/\D/', '', $in($k)) : $in($k)));
    }
    foreach (SECRET_SETTINGS as $k) {
        if (!empty($_POST["clear_$k"])) {
            q('DELETE FROM settings WHERE key = ?', [$k]);
        } elseif (($v = (string) ($_POST[$k] ?? '')) !== '') {
            set_setting($k, $v);
        }
    }
    set_setting('payfast_mode', $mode);
    flash('Settings saved.' . ($mode === 'live' ? ' Live payments are ON.' : ' Payments are in test (sandbox) mode.'));
    redirect('/admin/settings/');
}

admin_view('settings', [
    'title' => 'Settings',
    's' => array_combine(PLAIN_SETTINGS, array_map(fn ($k) => setting($k, ''), PLAIN_SETTINGS)),
    'has' => array_combine(SECRET_SETTINGS, array_map(fn ($k) => setting($k) !== null && setting($k) !== '', SECRET_SETTINGS)),
    'mode' => setting('payfast_mode', 'sandbox'),
]);
