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

/** "Delivery is not included": the active delivery options and their prices, e.g. "PUDO locker pickup R100,00 · PUDO door to door R120,00". */
function delivery_prices(): string
{
    $m = q('SELECT name, price_cents FROM shipping_methods WHERE active = 1 ORDER BY sort')->fetchAll();
    return implode(' · ', array_map(fn ($r) => e($r['name']) . ' ' . money((int) $r['price_cents']), $m));
}

/** Links to the shop's Instagram, Facebook and TikTok pages (only the ones filled in under Admin, Settings). */
function socials(string $class = ''): string
{
    $icons = [
        'instagram' => ['Instagram', '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1" fill="currentColor" stroke="none"/>'],
        'facebook' => ['Facebook', '<path d="M14.5 8H17V4.5h-2.5C12 4.5 10.5 6 10.5 8.6V11H8v3.5h2.5V21H14v-6.5h2.6l.4-3.5h-3V9c0-.6.4-1 .5-1z"/>'],
        'tiktok' => ['TikTok', '<path d="M14 3v11.5a3.5 3.5 0 1 1-3-3.46M14 3c.4 2.6 2.2 4.3 5 4.5"/>'],
    ];
    $out = '';
    foreach ($icons as $k => [$name, $svg]) {
        if ($url = setting($k)) {
            $out .= '<a href="' . e($url) . '" target="_blank" rel="noopener" aria-label="Alora on ' . $name . '"><svg viewBox="0 0 24 24" aria-hidden="true">' . $svg . '</svg><span>' . $name . '</span></a>';
        }
    }
    return $out ? '<p class="socials ' . $class . '">' . $out . '</p>' : '';
}

/** A pair of gold olive sprigs with two olives each and a little sparkle: the About page sign-off. */
function olive_sprig(string $class = ''): string
{
    return str_replace('class="sprig"', 'class="sprig ' . $class . '"', <<<'SVG'
<svg class="sprig" viewBox="0 0 300 130" aria-hidden="true">
<defs>
<linearGradient id="sp-gold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f3dcaa"/><stop offset=".45" stop-color="#d4ad6c"/><stop offset="1" stop-color="#a77d43"/></linearGradient>
<radialGradient id="sp-oliveg" cx=".35" cy=".3" r=".8"><stop offset="0" stop-color="#b9b25a"/><stop offset=".55" stop-color="#6f7a2e"/><stop offset="1" stop-color="#3f4a1c"/></radialGradient>
<g id="sp-leaf"><path d="M0 0 C7 -7.5 21 -9 33 0 C21 9 7 7.5 0 0Z" fill="url(#sp-gold)" stroke="#a77d43" stroke-width=".6"/><path d="M3 0 C12 -.6 22 -.6 30 0" fill="none" stroke="#a77d43" stroke-width=".7" opacity=".7"/></g>
<g id="sp-tip"><path d="M0 0 C7 -6 20 -7 30 0 C20 7 7 6 0 0Z" fill="url(#sp-gold)" stroke="#a77d43" stroke-width=".6"/></g>
<g id="sp-side"><path class="sp-stem" d="M150,112 C118,110 70,96 30,52"/><use href="#sp-leaf" transform="translate(133.5 110.0) rotate(-132.2) scale(0.95)"/><use href="#sp-leaf" transform="translate(119.9 107.1) rotate(-203.5) scale(1)"/><use href="#sp-leaf" transform="translate(103.0 101.8) rotate(-121.7) scale(1.08)"/><use href="#sp-leaf" transform="translate(86.7 94.8) rotate(-192.0) scale(1.1)"/><use href="#sp-leaf" transform="translate(70.1 85.6) rotate(-110.0) scale(1.05)"/><use href="#sp-leaf" transform="translate(54.8 74.9) rotate(-180.2) scale(0.95)"/><use href="#sp-leaf" transform="translate(39.7 62.0) rotate(-98.3) scale(0.85)"/><use href="#sp-tip" transform="translate(30 52) rotate(-132.3) scale(.8)"/><path class="sp-twig" d="M111.5 104.7 L105.1 114.9"/><ellipse class="sp-olive" cx="105.1" cy="117.9" rx="6.2" ry="8" transform="rotate(-327.6 105.1 117.9)"/><ellipse class="sp-shine" cx="103.1" cy="115.9" rx="1.6" ry="2.4"/><path class="sp-twig" d="M94.3 98.3 L96.0 86.4"/><ellipse class="sp-olive" cx="96.0" cy="89.4" rx="6.2" ry="8" transform="rotate(-171.7 96.0 89.4)"/><ellipse class="sp-shine" cx="94.0" cy="87.4" rx="1.6" ry="2.4"/></g>
</defs>
<use href="#sp-side"/>
<use href="#sp-side" transform="translate(300 0) scale(-1 1)"/>
<path class="sp-spark" d="M150 96 C151.5 104 152 104.5 160 106 C152 107.5 151.5 108 150 116 C148.5 108 148 107.5 140 106 C148 104.5 148.5 104 150 96Z"/>
</svg>
SVG);
}
