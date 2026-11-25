<?php
/** Staff accounts (owner only). New staff get an email link to choose their own password. */
if ($method === 'POST') {
    $do = $_POST['do'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($do === 'add') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
        $role = ($_POST['role'] ?? '') === 'owner' ? 'owner' : 'staff';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) < 2) {
            flash('Enter a name and a valid email.');
        } elseif (q('SELECT 1 FROM staff WHERE email = ?', [$email])->fetchColumn()) {
            flash('That email already has a staff account.');
        } else {
            q('INSERT INTO staff (email, name, role, password_hash) VALUES (?, ?, ?, ?)', [$email, $name, $role, password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT)]);
            send_reset('staff', $email);
            flash("$name has been emailed a link to set their password.");
        }
    } elseif ($do === 'remove' && $id && $id !== (int) $me['id']) {
        q('DELETE FROM staff WHERE id = ?', [$id]);
        flash('Staff account removed.');
    } elseif ($do === 'reset' && $id) {
        $email = q('SELECT email FROM staff WHERE id = ?', [$id])->fetchColumn();
        if ($email) {
            send_reset('staff', $email);
            flash("A password reset link was emailed to $email.");
        }
    }
    redirect('/admin/staff/');
}
admin_view('staff', ['title' => 'Staff', 'people' => q('SELECT id, email, name, role, created_at, locked_until FROM staff ORDER BY role DESC, name')->fetchAll()]);
