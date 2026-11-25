<?php
/** Products: list, add, edit (prices, stock, categories, photos, bundle contents), hide. */
$id = ($parts[1] ?? '') === 'new' ? 0 : (int) ($parts[1] ?? -1);

if ($id === -1) {
    $search = trim((string) ($_GET['q'] ?? ''));
    $cat = (int) ($_GET['cat'] ?? 0);
    $show = $_GET['show'] ?? 'active';
    $where = [match ($show) { 'hidden' => 'p.active = 0', 'low' => 'p.active = 1 AND p.stock_qty IS NOT NULL AND p.stock_qty <= 1', 'all' => '1', default => 'p.active = 1' }];
    $args = [];
    if ($search !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; array_push($args, "%$search%", "%$search%"); }
    if ($cat) { $where[] = 'p.id IN (SELECT product_id FROM product_categories WHERE category_id = ?)'; $args[] = $cat; }
    admin_view('products', [
        'title' => 'Products',
        'items' => q('SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort LIMIT 1) AS image
                      FROM products p WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id DESC LIMIT 500', $args)->fetchAll(),
        'cats' => q('SELECT id, name FROM categories ORDER BY sort')->fetchAll(),
        'search' => $search, 'cat' => $cat, 'show' => $show,
    ]);
    return;
}

$p = $id ? (q('SELECT * FROM products WHERE id = ?', [$id])->fetch() ?: not_found()) : null;
$errors = [];

if ($method === 'POST') {
    $do = $_POST['do'] ?? 'save';

    if ($do === 'image-delete' && $p) {
        $img = q('SELECT * FROM product_images WHERE id = ? AND product_id = ?', [(int) $_POST['image'], $id])->fetch();
        if ($img) {
            q('DELETE FROM product_images WHERE id = ?', [$img['id']]);
            delete_webp($img['path']);
            flash('Photo removed.');
        }
        redirect("/admin/products/$id/");
    }
    if ($do === 'image-main' && $p) {
        q('UPDATE product_images SET sort = sort + 1 WHERE product_id = ?', [$id]);
        q('UPDATE product_images SET sort = 0 WHERE id = ? AND product_id = ?', [(int) $_POST['image'], $id]);
        flash('Main photo changed.');
        redirect("/admin/products/$id/");
    }
    if ($do === 'toggle' && $p) {
        q('UPDATE products SET active = 1 - active, updated_at = datetime(\'now\') WHERE id = ?', [$id]);
        flash($p['active'] ? 'Product hidden from the shop.' : 'Product is back in the shop.');
        redirect("/admin/products/$id/");
    }

    // Save the product.
    $name = mb_substr(trim((string) $_POST['name']), 0, 160);
    $price = parse_rand($_POST['price'] ?? '');
    $was = parse_rand($_POST['was'] ?? '');
    $stockRaw = trim((string) ($_POST['stock'] ?? ''));
    $stock = $stockRaw === '' ? null : (ctype_digit($stockRaw) ? (int) $stockRaw : false);
    if (mb_strlen($name) < 2) $errors['name'] = 'Give the product a name.';
    if ($price === null) $errors['price'] = 'Enter a price, e.g. 120 or 120,50.';
    if ($stock === false) $errors['stock'] = 'Stock must be a whole number, or empty for "always available".';
    if ($was !== null && $price !== null && $was <= $price) $errors['was'] = 'The "was" price must be higher than the price (or leave it empty).';

    if (!$errors) {
        $slug = unique_slug('products', slugify(trim((string) ($_POST['slug'] ?? '')) ?: $name), $id ?: null);
        $data = [$name, $slug, trim((string) ($_POST['sku'] ?? '')) ?: null, clean_html((string) ($_POST['short_desc'] ?? '')),
            clean_html((string) ($_POST['description'] ?? '')), $price, $was ?? $price, $stock, empty($_POST['featured']) ? 0 : 1];
        db()->beginTransaction();
        if ($id) {
            q("UPDATE products SET name=?, slug=?, sku=?, short_desc=?, description=?, price_cents=?, regular_cents=?, stock_qty=?, featured=?, updated_at=datetime('now') WHERE id = ?", [...$data, $id]);
            if ($p['slug'] !== $slug) {
                q('INSERT OR REPLACE INTO redirects VALUES (?, ?)', ["/product/{$p['slug']}/", "/product/$slug/"]);
            }
        } else {
            q('INSERT INTO products (name, slug, sku, short_desc, description, price_cents, regular_cents, stock_qty, featured) VALUES (?,?,?,?,?,?,?,?,?)', $data);
            $id = (int) db()->lastInsertId();
        }
        q('DELETE FROM product_categories WHERE product_id = ?', [$id]);
        foreach ((array) ($_POST['cats'] ?? []) as $c) {
            q('INSERT OR IGNORE INTO product_categories SELECT ?, id FROM categories WHERE id = ?', [$id, (int) $c]);
        }
        // Bundle contents: rows of product + quantity. A product with contents is a bundle.
        q('DELETE FROM bundle_items WHERE bundle_id = ?', [$id]);
        foreach ((array) ($_POST['bundle_product'] ?? []) as $i => $bp) {
            $bp = (int) $bp;
            $bq = max(1, (int) ($_POST['bundle_qty'][$i] ?? 1));
            if ($bp && $bp !== $id) {
                q('INSERT OR REPLACE INTO bundle_items VALUES (?, ?, ?)', [$id, $bp, $bq]);
            }
        }
        q('UPDATE products SET is_bundle = EXISTS (SELECT 1 FROM bundle_items WHERE bundle_id = ?) WHERE id = ?', [$id, $id]);
        db()->commit();

        $uploadErrors = [];
        $next = (int) q('SELECT COALESCE(MAX(sort), -1) + 1 FROM product_images WHERE product_id = ?', [$id])->fetchColumn();
        foreach (uploaded_list('photos') as $f) {
            $r = save_upload($f, 'p/' . $slug);
            if (is_string($r)) {
                $uploadErrors[] = $f['name'] . ': ' . $r;
                continue;
            }
            [$path, $w, $h, $orig] = $r;
            q('INSERT INTO product_images (product_id, original, path, alt, width, height, sort) VALUES (?,?,?,?,?,?,?)', [$id, $orig, $path, $name, $w, $h, $next++]);
        }
        flash('Saved.' . ($uploadErrors ? ' Some photos were not added: ' . implode('; ', $uploadErrors) : ''));
        redirect("/admin/products/$id/");
    }
    $p = array_merge($p ?? [], ['name' => $name, 'price_cents' => $price, 'regular_cents' => $was ?? $price, 'stock_qty' => $stock === false ? null : $stock,
        'sku' => $_POST['sku'] ?? '', 'short_desc' => $_POST['short_desc'] ?? '', 'description' => $_POST['description'] ?? '', 'slug' => $_POST['slug'] ?? '']);
}

admin_view('product', [
    'title' => $id ? $p['name'] : 'New product',
    'p' => $p,
    'id' => $id,
    'errors' => $errors,
    'images' => $id ? q('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort', [$id])->fetchAll() : [],
    'cats' => q('SELECT id, name FROM categories ORDER BY sort')->fetchAll(),
    'mine' => $id ? q('SELECT category_id FROM product_categories WHERE product_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN) : [],
    'bundle' => $id ? q('SELECT b.product_id, b.qty, p.name, p.stock_qty FROM bundle_items b JOIN products p ON p.id = b.product_id WHERE b.bundle_id = ?', [$id])->fetchAll() : [],
    'all' => q('SELECT id, name FROM products WHERE active = 1 ORDER BY name COLLATE NOCASE')->fetchAll(),
    'sold' => $id ? (int) q("SELECT COALESCE(SUM(oi.qty), 0) FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.product_id = ? AND o.status NOT IN ('pending','cancelled')", [$id])->fetchColumn() : 0,
]);
