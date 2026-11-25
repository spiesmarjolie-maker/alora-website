<?php
/** Small shared view pieces. */

function card(array $p, string $sizes = '(min-width: 1100px) 22vw, (min-width: 700px) 30vw, 46vw'): string
{
    $url = '/product/' . e($p['slug']) . '/';
    $sale = $p['regular_cents'] > $p['price_cents'];
    $left = $p['stock_qty'] === null ? null : (int) $p['stock_qty'];
    $badge = $left === 0 ? '<span class="badge badge--out">Sold out</span>'
        : ($sale ? '<span class="badge">Sale</span>' : ($left === 1 ? '<span class="badge badge--last">Last one</span>' : ''));
    $btn = $left === 0
        ? '<a class="card__btn card__btn--ghost" href="' . $url . '">View</a>'
        : '<button class="card__btn" data-add="' . (int) $p['id'] . '" data-name="' . e($p['name']) . '">Add to bag</button>';
    return '<article class="card" data-reveal>'
        . '<a class="card__img" href="' . $url . '">' . img($p['image'], $p['name'], $sizes, 'loading="lazy"') . $badge . '</a>'
        . '<h3 class="card__name"><a href="' . $url . '">' . e($p['name']) . '</a></h3>'
        . '<p class="card__price">' . ($sale ? '<del>' . money($p['regular_cents']) . '</del> ' : '') . money($p['price_cents']) . '</p>'
        . $btn . '</article>';
}

/** The gold olive branch drawn in SVG (stem, leaves, two olives). Animated by stroke drawing. */
function olive_branch(string $class = ''): string
{
    return <<<SVG
<svg class="branch $class" viewBox="0 0 320 220" aria-hidden="true">
  <path class="draw" d="M10 200 C 70 170, 120 140, 170 100 S 260 40, 310 20" />
  <path class="draw leaf" d="M90 160 c -6 -26 8 -44 30 -52 c 2 24 -10 42 -30 52z" />
  <path class="draw leaf" d="M128 132 c 16 -20 40 -22 60 -14 c -14 18 -38 24 -60 14z" />
  <path class="draw leaf" d="M160 104 c -4 -28 12 -46 34 -52 c 0 26 -14 44 -34 52z" />
  <path class="draw leaf" d="M205 72 c 18 -16 42 -14 58 -2 c -18 14 -40 16 -58 2z" />
  <path class="draw leaf" d="M236 50 c 0 -26 18 -40 40 -42 c -4 24 -20 38 -40 42z" />
  <path class="draw leaf" d="M275 30 c 14 -12 30 -10 38 -2 c -12 10 -26 12 -38 2z" />
  <ellipse class="olive" cx="150" cy="150" rx="11" ry="14" transform="rotate(-20 150 150)" />
  <ellipse class="olive" cx="176" cy="160" rx="11" ry="14" transform="rotate(10 176 160)" />
  <path class="draw" d="M150 136 q 4 -16 14 -24 M176 146 q -2 -18 -10 -32" />
</svg>
SVG;
}
