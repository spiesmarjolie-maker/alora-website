<?php
declare(strict_types=1);

/** Paths. app/ and storage/ live outside the public web root. */
const ROOT = __DIR__ . '/..';
const STORAGE = ROOT . '/storage';
const PUBLIC_DIR = ROOT . '/public';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $file = getenv('ALORA_DB') ?: STORAGE . '/alora.sqlite';   // tests point this at a throwaway copy
    $fresh = !is_file($file);
    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000;');
    if ($fresh) {
        $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
    }
    return $pdo;
}

function q(string $sql, array $args = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}

function setting(string $key, ?string $default = null): ?string
{
    $v = q('SELECT value FROM settings WHERE key = ?', [$key])->fetchColumn();
    return $v === false ? $default : $v;
}

function set_setting(string $key, string $value): void
{
    q('INSERT INTO settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value', [$key, $value]);
}
