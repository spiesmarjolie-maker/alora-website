<?php
/** Staff sign in, forgot password, reset. */
$err = null;
switch ($parts[0]) {
    case 'login':
        if ($method === 'POST') {
            $r = attempt_login('staff', (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
            if (is_array($r)) {
                $_SESSION['staff_id'] = $r['id'];
                $to = $_SESSION['admin_after_login'] ?? '/admin/';
                unset($_SESSION['admin_after_login']);
                redirect(str_starts_with($to, '/admin/') ? $to : '/admin/');
            }
            $err = $r;
        }
        admin_view('login', ['title' => 'Staff sign in', 'err' => $err, 'bare' => true]);
        break;

    case 'forgot':
        if ($method === 'POST') {
            send_reset('staff', (string) ($_POST['email'] ?? ''));
            flash('If that email belongs to a staff account, a reset link is on its way.');
            redirect('/admin/login/');
        }
        admin_view('forgot', ['title' => 'Reset staff password', 'bare' => true]);
        break;

    case 'reset':
        $token = (string) ($_GET['t'] ?? '');
        if (!use_reset('staff', $token)) {
            flash('That link has expired or was already used. Ask for a new one.');
            redirect('/admin/forgot/');
        }
        if ($method === 'POST') {
            $pw = (string) ($_POST['password'] ?? '');
            if (!($err = password_problem($pw))) {
                $_SESSION['staff_id'] = use_reset('staff', $token, $pw);
                flash('Password saved. Welcome!');
                redirect('/admin/');
            }
        }
        admin_view('reset', ['title' => 'Choose a password', 'err' => $err, 'bare' => true, 'action' => '/admin/reset/?t=' . urlencode($token)]);
        break;
}
