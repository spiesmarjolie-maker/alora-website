<?php
// Teachers Box: a personalised plastic stationery box set (R150) with the customer's name, grade/subject and
// design (or their own picture), optional add-ons, and any extra stationery from the shop.
// The uploaded picture is kept outside public/ (storage/uploads/teachers-box) and only staff can open it.
const TB_COLOURS = ['blue' => 'Sky blue', 'purple' => 'Purple', 'green' => 'Green', 'pink' => 'Pink', 'lilac' => 'Lilac'];
const TB_THEMES = ['florals' => 'Florals', 'sea' => 'Under the sea', 'rainbow' => 'Sunshine & rainbow',
    'science' => 'Science & nature', 'own' => 'My own picture'];
const TB_ADDONS = ['teachers-box-sticker-pack', 'teachers-box-sticky-notes', 'teachers-box-eraser', 'teachers-box-stapler'];
const TB_UPLOAD_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/heic' => 'heic', 'image/heif' => 'heic'];

$box = q("SELECT " . PRODUCT_COLS . " FROM products p WHERE p.slug = 'teachers-box' AND p.active = 1")->fetch() ?: not_found();
$addons = q("SELECT " . PRODUCT_COLS . " FROM products p WHERE p.active = 1 AND p.slug IN ('" . implode("','", TB_ADDONS) . "')
             ORDER BY p.price_cents")->fetchAll();
// Extra stationery: anything in stock in Stationery Lovers.
$extras = products("(p.stock_qty IS NULL OR p.stock_qty > 0) AND p.id IN (SELECT pc.product_id FROM product_categories pc
                    JOIN categories c ON c.id = pc.category_id WHERE c.slug = 'stationary-lovers')", [], 'p.name');

if ($method === 'POST') {
    csrf_check();
    $v = fn ($k, $max) => mb_substr(trim(preg_replace('/\s+/', ' ', (string) ($_POST[$k] ?? ''))), 0, $max);
    $name = $v('box_name', 30);
    $line = $v('box_line', 50);
    $colour = array_key_exists($_POST['colour'] ?? '', TB_COLOURS) ? $_POST['colour'] : 'blue';
    $theme = array_key_exists($_POST['theme'] ?? '', TB_THEMES) ? $_POST['theme'] : 'florals';
    $wish = $v('wish', 300);
    // Back to the form with a message, keeping what they typed.
    $back = function (string $msg) use ($name, $line, $colour, $theme, $wish): never {
        flash($msg);
        $_SESSION['tb_form'] = compact('name', 'line', 'colour', 'theme', 'wish');
        redirect('/teachers-box/');
    };

    if (mb_strlen($name) < 2) {
        $back('Please type the name for the box (for example "Ms Spies").');
    }

    // The customer's own picture (optional; needed for the "My own picture" design).
    $upload = null;
    $f = $_FILES['picture'] ?? null;
    if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 10 * 1024 * 1024) {
            $back('That picture could not be uploaded. Please use a photo under 10 MB.');
        }
        $type = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = TB_UPLOAD_TYPES[$type] ?? null;
        if (!$ext || ($ext !== 'heic' && !@getimagesize($f['tmp_name']))) {
            $back('Please upload a photo (JPG, PNG, WEBP or iPhone HEIC).');
        }
        $dir = STORAGE . '/uploads/teachers-box';
        is_dir($dir) || mkdir($dir, 0750, true);
        $upload = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], "$dir/$upload")) {
            $back('That picture could not be saved. Please try again.');
        }
    }
    if ($theme === 'own' && !$upload) {
        $back('Please choose the picture you want on the box, or pick one of our designs.');
    }

    // Add-ons and extra stationery: only ids that are really on offer here.
    $offered = array_column($addons, null, 'id') + array_column($extras, null, 'id');
    $ids = array_unique(array_map('intval', array_merge((array) ($_POST['addons'] ?? []), (array) ($_POST['extras'] ?? []))));
    $picked = array_values(array_intersect_key($offered, array_flip($ids)));

    $bag = bag();
    $bag[(int) $box['id']] = ($bag[(int) $box['id']] ?? 0) + 1;
    foreach ($picked as $p) {
        $bag[(int) $p['id']] = ($bag[(int) $p['id']] ?? 0) + 1;
    }
    $_SESSION['bag'] = $bag;

    // What staff need to make it: goes into the order note at checkout.
    $_SESSION['teachers_boxes'][] = 'TEACHERS BOX: name "' . $name . '"' . ($line !== '' ? ', line 2 "' . $line . '"' : '')
        . ', box colour ' . TB_COLOURS[$colour] . ', design ' . TB_THEMES[$theme]
        . ($upload ? ', picture /admin/uploads/' . $upload : '')
        . ($picked ? '. Pack inside: ' . implode(', ', array_column($picked, 'name')) : '')
        . ($wish !== '' ? '. Wishes: ' . $wish : '') . '.';
    unset($_SESSION['tb_form']);
    flash('Your Teachers Box for "' . $name . '" is in your bag.');
    redirect('/bag/');
}

$form = $_SESSION['tb_form'] ?? [];
unset($_SESSION['tb_form']);
foreach ($extras as &$p) {
    $p['cut'] = $p['image'] && is_file(PUBLIC_DIR . "/media/{$p['image']}-cut-600.webp") ? "/media/{$p['image']}-cut-600.webp" : null;
}
unset($p);

view('teachers-box', [
    'title' => 'Teachers Box',
    'description' => 'Personalised Alora Teachers Box: a stationery box with your name, grade and design, filled with gel pens, a highlighter and more, for '
        . money((int) $box['price_cents']) . ' a set.',
    'box' => $box,
    'addons' => $addons,
    'extras' => $extras,
    'form' => $form,
]);
