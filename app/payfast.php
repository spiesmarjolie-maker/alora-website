<?php
declare(strict_types=1);
/**
 * Payfast (South Africa) redirect checkout + ITN (Instant Transaction Notification).
 * Docs: https://developers.payfast.co.za/docs#step_1_form_fields and #step_4_confirm_payment
 *
 * Keys live in settings (entered by the owner in admin). Until live keys are saved the shop runs in
 * sandbox mode with Payfast's public test merchant, so no real money can move.
 */

const PF_SANDBOX_ID = '10000100';
const PF_SANDBOX_KEY = '46f0cd694581a';
const PF_SANDBOX_PASS = '';

function pf_sandbox(): bool
{
    return setting('payfast_mode', 'sandbox') !== 'live';
}

function pf_host(): string
{
    return pf_sandbox() ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
}

function pf_creds(): array
{
    return pf_sandbox()
        // The owner's own sandbox account if entered in admin, otherwise Payfast's shared test merchant.
        ? (setting('payfast_sandbox_id')
            ? [setting('payfast_sandbox_id'), (string) setting('payfast_sandbox_key', ''), (string) setting('payfast_sandbox_passphrase', '')]
            : [PF_SANDBOX_ID, PF_SANDBOX_KEY, PF_SANDBOX_PASS])
        : [setting('payfast_merchant_id', ''), setting('payfast_merchant_key', ''), setting('payfast_passphrase', '')];
}

/** Payfast's signature: fields in the order given, url-encoded (spaces as +), plus the passphrase, MD5. */
function pf_signature(array $fields, string $passphrase): string
{
    $pairs = [];
    foreach ($fields as $k => $v) {
        if ($k === 'signature' || $v === '' || $v === null) {
            continue;
        }
        $pairs[] = $k . '=' . urlencode(trim((string) $v));
    }
    $str = implode('&', $pairs);
    if ($passphrase !== '') {
        $str .= '&passphrase=' . urlencode(trim($passphrase));
    }
    return md5($str);
}

/** The form fields for an order, in the order Payfast requires. */
function pf_form(array $order): array
{
    [$id, $key, $pass] = pf_creds();
    $name = preg_split('/\s+/', trim($order['name']), 2);
    $f = [
        'merchant_id' => $id,
        'merchant_key' => $key,
        'return_url' => order_url($order),
        'cancel_url' => site_url('/checkout/?cancelled=' . rawurlencode($order['number'])),
        'notify_url' => site_url('/payfast/notify/'),
        'name_first' => mb_substr($name[0], 0, 100),
        'name_last' => mb_substr($name[1] ?? '', 0, 100),
        'email_address' => $order['email'],
        'm_payment_id' => $order['number'],
        'amount' => number_format($order['total_cents'] / 100, 2, '.', ''),
        'item_name' => mb_substr(setting('shop_name', 'Alora') . ' order ' . $order['number'], 0, 100),
    ];
    // Payfast's shared public test merchant has an unpublished passphrase (tested 23 Nov 2026: every signature
    // is rejected, unsigned forms are accepted). With the owner's own sandbox or live account we always sign.
    if (!pf_shared_sandbox()) {
        $f['signature'] = pf_signature($f, $pass);
    }
    return $f;
}

function pf_shared_sandbox(): bool
{
    return pf_sandbox() && pf_creds()[0] === PF_SANDBOX_ID;
}

/** The ITN parameter string: every field Payfast sent, in order, up to "signature" (empty ones included). */
function pf_itn_string(array $post): string
{
    $pairs = [];
    foreach ($post as $k => $v) {
        if ($k === 'signature') {
            break;
        }
        $pairs[] = $k . '=' . urlencode((string) $v);
    }
    return implode('&', $pairs);
}

/** Check an ITN. Returns null when valid, otherwise the reason it was rejected. */
function pf_check_itn(array $post, array $order): ?string
{
    [, , $pass] = pf_creds();
    $params = pf_itn_string($post);

    // 1. Signature (not possible with the shared test merchant: its passphrase is unknown;
    //    steps 2-4 still apply, and step 4 asks Payfast itself to confirm).
    $str = $params . ($pass !== '' ? '&passphrase=' . urlencode(trim($pass)) : '');
    if (!pf_shared_sandbox() && !hash_equals(md5($str), (string) ($post['signature'] ?? ''))) {
        return 'bad signature';
    }

    // 2. It must come from a Payfast server.
    if (!is_dev()) {
        $ips = [];
        foreach (['www.payfast.co.za', 'sandbox.payfast.co.za', 'w1w.payfast.co.za', 'w2w.payfast.co.za'] as $h) {
            $ips = array_merge($ips, gethostbynamel($h) ?: []);
        }
        if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', array_unique($ips), true)) {
            return 'not from Payfast';
        }
    }

    // 3. The amount paid must match the order total.
    if (abs((float) ($post['amount_gross'] ?? 0) - $order['total_cents'] / 100) > 0.01) {
        return 'amount mismatch';
    }

    // 4. Ask Payfast to confirm this notification.
    if (!is_dev()) {
        $c = curl_init('https://' . pf_host() . '/eng/query/validate');
        curl_setopt_array($c, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'Alora shop',
        ]);
        $answer = trim((string) curl_exec($c));
        if ($answer !== 'VALID') {
            return 'Payfast did not confirm (' . $answer . ')';
        }
    }
    return null;
}
