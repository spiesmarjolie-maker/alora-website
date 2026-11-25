<?php
$sum = fn (string $since) => (int) q("SELECT COALESCE(SUM(total_cents), 0) FROM orders WHERE status NOT IN ('pending', 'cancelled') AND paid_at >= ?", [$since])->fetchColumn();
admin_view('dashboard', [
    'title' => 'Dashboard',
    'toPack' => (int) q("SELECT COUNT(*) FROM orders WHERE status = 'paid'")->fetchColumn(),
    'toShip' => (int) q("SELECT COUNT(*) FROM orders WHERE status = 'packed'")->fetchColumn(),
    'today' => $sum(date('Y-m-d 00:00:00')),
    'month' => $sum(date('Y-m-01 00:00:00')),
    'monthOrders' => (int) q("SELECT COUNT(*) FROM orders WHERE status NOT IN ('pending', 'cancelled') AND paid_at >= ?", [date('Y-m-01')])->fetchColumn(),
    'low' => q('SELECT id, name, stock_qty FROM products WHERE active = 1 AND stock_qty IS NOT NULL AND stock_qty <= 1 ORDER BY stock_qty, name LIMIT 12')->fetchAll(),
    'lowCount' => (int) q('SELECT COUNT(*) FROM products WHERE active = 1 AND stock_qty IS NOT NULL AND stock_qty <= 1')->fetchColumn(),
    'recent' => q("SELECT * FROM orders WHERE status != 'pending' ORDER BY id DESC LIMIT 8")->fetchAll(),
    'noPrice' => q('SELECT id, name FROM products WHERE active = 1 AND price_cents = 0')->fetchAll(),
    'views' => (int) q('SELECT COALESCE(SUM(views), 0) FROM page_views WHERE day >= ?', [date('Y-m-d', strtotime('-6 days'))])->fetchColumn(),
]);
