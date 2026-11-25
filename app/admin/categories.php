<?php
/** Categories: name, description, order, show/hide, picture. */
if ($method === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80);
    if (mb_strlen($name) < 2) {
        flash('A category needs a name.');
        redirect('/admin/categories/');
    }
    $fields = [$name, mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 500), (int) ($_POST['sort'] ?? 0), empty($_POST['active']) ? 0 : 1];
    if ($id) {
        q('UPDATE categories SET name = ?, description = ?, sort = ?, active = ? WHERE id = ?', [...$fields, $id]);
    } else {
        q('INSERT INTO categories (name, description, sort, active, slug) VALUES (?, ?, ?, ?, ?)', [...$fields, unique_slug('categories', slugify($name))]);
        $id = (int) db()->lastInsertId();
    }
    foreach (uploaded_list('photo') as $f) {
        $slug = q('SELECT slug FROM categories WHERE id = ?', [$id])->fetchColumn();
        $r = save_upload($f, 'c/' . $slug);
        if (is_string($r)) {
            flash("Saved, but the picture was not added: $r");
            redirect('/admin/categories/');
        }
        $old = q('SELECT image FROM categories WHERE id = ?', [$id])->fetchColumn();
        q('UPDATE categories SET image = ? WHERE id = ?', [$r[0], $id]);
        if ($old) delete_webp($old);
    }
    flash("“{$name}” saved.");
    redirect('/admin/categories/');
}
admin_view('categories', [
    'title' => 'Categories',
    'cats' => q('SELECT c.*, (SELECT COUNT(*) FROM product_categories pc JOIN products p ON p.id = pc.product_id WHERE pc.category_id = c.id AND p.active = 1) AS n
                 FROM categories c ORDER BY sort')->fetchAll(),
]);
