<?php
declare(strict_types=1);
/**
 * Create a staff account from the command line (use this once for the shop owner).
 * Prints a one-hour link where the person chooses their own password; no password is ever shown.
 *   php tools/add-staff.php owner@example.co.za "Owner Name" owner https://yoursite.co.za
 */
require __DIR__ . '/../app/db.php';

[, $email, $name, $role, $base] = $argv + [null, null, null, 'staff', 'http://localhost:8080'];
if (!filter_var((string) $email, FILTER_VALIDATE_EMAIL) || !$name) {
    exit("Usage: php tools/add-staff.php EMAIL \"NAME\" [owner|staff] [SITE_URL]\n");
}
$role = $role === 'owner' ? 'owner' : 'staff';
q('INSERT INTO staff (email, name, role, password_hash) VALUES (?, ?, ?, ?)
   ON CONFLICT(email) DO UPDATE SET name = excluded.name, role = excluded.role',
    [$email, $name, $role, password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT)]);
$id = (int) q('SELECT id FROM staff WHERE email = ?', [$email])->fetchColumn();
$token = bin2hex(random_bytes(32));
q("DELETE FROM password_resets WHERE who = 'staff' AND user_id = ?", [$id]);
q("INSERT INTO password_resets VALUES (?, 'staff', ?, ?)", [hash('sha256', $token), $id, date('Y-m-d H:i:s', time() + 3600)]);
echo "$name ($role) is ready. Open this link within an hour to choose a password:\n" . rtrim($base, '/') . "/admin/reset/?t=$token\n";
