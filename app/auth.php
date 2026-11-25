<?php
declare(strict_types=1);
/** Logins for customers and staff: hashed passwords, lockout after repeated failures, email resets. */

const MAX_FAILS = 5;
const LOCK_MINUTES = 15;

/** Returns the user row, or an error message string. $table is 'customers' or 'staff'. */
function attempt_login(string $table, string $email, string $password): array|string
{
    $u = q("SELECT * FROM $table WHERE email = ?", [trim($email)])->fetch();
    // Same work whether or not the account exists, so timing does not reveal which emails are registered.
    $hash = $u['password_hash'] ?? password_hash('no such account', PASSWORD_DEFAULT);
    $ok = password_verify($password, $hash);

    if ($u && $u['locked_until'] && $u['locked_until'] > date('Y-m-d H:i:s')) {
        return 'Too many attempts. Please try again in ' . LOCK_MINUTES . ' minutes or reset your password.';
    }
    if (!$u || !$u['password_hash'] || !$ok) {
        if ($u) {
            $fails = $u['failed_logins'] + 1;
            q("UPDATE $table SET failed_logins = ?, locked_until = ? WHERE id = ?",
                [$fails >= MAX_FAILS ? 0 : $fails, $fails >= MAX_FAILS ? date('Y-m-d H:i:s', time() + LOCK_MINUTES * 60) : null, $u['id']]);
        }
        return 'That email and password do not match.';
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q("UPDATE $table SET password_hash = ? WHERE id = ?", [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    q("UPDATE $table SET failed_logins = 0, locked_until = NULL WHERE id = ?", [$u['id']]);
    session_regenerate_id(true);
    return $u;
}

function password_problem(string $pw): ?string
{
    return mb_strlen($pw) < 10 ? 'Use at least 10 characters for your password.' : null;
}

/** Email a one-hour reset link. Always behaves the same whether or not the email exists. */
function send_reset(string $who, string $email): void
{
    $table = $who === 'staff' ? 'staff' : 'customers';
    $u = q("SELECT id, name, email FROM $table WHERE email = ?", [trim($email)])->fetch();
    if (!$u) {
        return;
    }
    $token = bin2hex(random_bytes(32));
    q('DELETE FROM password_resets WHERE who = ? AND user_id = ?', [$who, $u['id']]);
    q('INSERT INTO password_resets VALUES (?, ?, ?, ?)', [hash('sha256', $token), $who, $u['id'], date('Y-m-d H:i:s', time() + 3600)]);
    $url = site_url(($who === 'staff' ? '/admin/reset/' : '/account/reset/') . '?t=' . $token);
    $html = email_html('reset', ['name' => $u['name'], 'url' => $url]);
    send_mail($u['email'], 'Reset your ' . setting('shop_name', 'Alora') . ' password', $html);
}

/** Use a reset token: returns the user id or null. The token works once. */
function use_reset(string $who, string $token, ?string $newPassword = null): ?int
{
    $row = q('SELECT * FROM password_resets WHERE token_hash = ? AND who = ? AND expires_at > ?',
        [hash('sha256', $token), $who, date('Y-m-d H:i:s')])->fetch();
    if (!$row) {
        return null;
    }
    if ($newPassword !== null) {
        $table = $who === 'staff' ? 'staff' : 'customers';
        q("UPDATE $table SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?",
            [password_hash($newPassword, PASSWORD_DEFAULT), $row['user_id']]);
        q('DELETE FROM password_resets WHERE token_hash = ?', [$row['token_hash']]);
        session_regenerate_id(true);
    }
    return (int) $row['user_id'];
}

function customer(): ?array
{
    static $c = false;
    if ($c === false) {
        $c = isset($_SESSION['customer_id']) ? (q('SELECT * FROM customers WHERE id = ?', [$_SESSION['customer_id']])->fetch() ?: null) : null;
    }
    return $c;
}

function staff(): ?array
{
    static $s = false;
    if ($s === false) {
        $s = isset($_SESSION['staff_id']) ? (q('SELECT * FROM staff WHERE id = ?', [$_SESSION['staff_id']])->fetch() ?: null) : null;
    }
    return $s;
}
