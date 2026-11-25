<?php
/** /account/… : sign in, create account, sign out, forgot + reset password, details, order history. */
$sub = trim(substr($path, strlen('/account/')), '/');
$errors = [];
$v = fn ($k, $max = 200) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);

if ($method === 'POST') {
    csrf_check();
}

switch ($sub) {
    case 'login':
        if ($method === 'POST') {
            $r = attempt_login('customers', $v('email'), (string) ($_POST['password'] ?? ''));
            if (is_array($r)) {
                $_SESSION['customer_id'] = $r['id'];
                $to = $_SESSION['after_login'] ?? '/account/';
                unset($_SESSION['after_login']);
                redirect($to);
            }
            $errors['login'] = $r;
        }
        break;

    case 'register':
        if ($method === 'POST') {
            $email = $v('email', 160);
            $pw = (string) ($_POST['password'] ?? '');
            if (mb_strlen($v('name')) < 2) $errors['name'] = 'Please enter your name.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
            if ($p = password_problem($pw)) $errors['password'] = $p;
            if (empty($_POST['agree'])) $errors['agree'] = 'Please accept the privacy notice.';
            if (!$errors) {
                $exists = q('SELECT id, password_hash FROM customers WHERE email = ?', [$email])->fetch();
                if ($exists && $exists['password_hash']) {
                    $errors['email'] = 'There is already an account for this email. Sign in or reset your password.';
                } else {
                    q('INSERT INTO customers (email, password_hash, name, phone) VALUES (?, ?, ?, ?)
                       ON CONFLICT(email) DO UPDATE SET password_hash = excluded.password_hash, name = excluded.name, phone = excluded.phone',
                        [$email, password_hash($pw, PASSWORD_DEFAULT), $v('name', 120), $v('phone', 30)]);
                    session_regenerate_id(true);
                    $_SESSION['customer_id'] = (int) q('SELECT id FROM customers WHERE email = ?', [$email])->fetchColumn();
                    flash('Welcome to Alora! Your account is ready.');
                    $to = $_SESSION['after_login'] ?? '/account/';
                    unset($_SESSION['after_login']);
                    redirect($to);
                }
            }
        }
        break;

    case 'logout':
        if ($method === 'POST') {
            unset($_SESSION['customer_id'], $_SESSION['checkout']);
            session_regenerate_id(true);
            flash('You are signed out.');
        }
        redirect('/');

    case 'forgot':
        if ($method === 'POST') {
            send_reset('customer', $v('email'));
            flash('If that email has an account, a reset link is on its way. Check your inbox (and spam).');
            redirect('/account/');
        }
        view('account-forgot', ['title' => 'Reset your password']);
        return;

    case 'reset':
        $token = (string) ($_GET['t'] ?? '');
        if (!use_reset('customer', $token)) {
            flash('That reset link has expired or was already used. Please ask for a new one.');
            redirect('/account/forgot/');
        }
        if ($method === 'POST') {
            $pw = (string) ($_POST['password'] ?? '');
            if ($p = password_problem($pw)) {
                $errors['password'] = $p;
            } else {
                $_SESSION['customer_id'] = use_reset('customer', $token, $pw);
                flash('Your new password is saved.');
                redirect('/account/');
            }
        }
        view('account-reset', ['title' => 'Choose a new password', 'errors' => $errors, 'action' => '/account/reset/?t=' . urlencode($token)]);
        return;

    case 'details':
        $c = customer() ?? redirect('/account/');
        if ($method === 'POST') {
            $addr = ['street' => $v('street'), 'suburb' => $v('suburb', 100), 'city' => $v('city', 100), 'postcode' => $v('postcode', 10),
                'province' => in_array($v('province', 40), PROVINCES, true) ? $v('province', 40) : '', 'locker' => $v('locker', 160)];
            q('UPDATE customers SET name = ?, phone = ?, address_json = ? WHERE id = ?',
                [$v('name', 120) ?: $c['name'], $v('phone', 30), json_encode($addr, JSON_UNESCAPED_UNICODE), $c['id']]);
            if (($new = (string) ($_POST['new_password'] ?? '')) !== '') {
                if (!password_verify((string) ($_POST['current_password'] ?? ''), (string) $c['password_hash'])) {
                    flash('Details saved, but your current password was not right, so the password was not changed.');
                    redirect('/account/');
                }
                if ($p = password_problem($new)) {
                    flash("Details saved. Password not changed: $p");
                    redirect('/account/');
                }
                q('UPDATE customers SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $c['id']]);
            }
            flash('Your details are saved.');
        }
        redirect('/account/');

    case '':
        break;

    default:
        not_found();
}

$c = customer();
if (!$c) {
    view('account-signin', ['title' => 'My account', 'errors' => $errors, 'in' => $_POST]);
    return;
}
view('account', [
    'title' => 'My account',
    'c' => $c,
    'addr' => json_decode((string) $c['address_json'], true) ?: [],
    'orders' => q("SELECT * FROM orders WHERE customer_id = ? AND status != 'pending' ORDER BY id DESC", [$c['id']])->fetchAll(),
]);
