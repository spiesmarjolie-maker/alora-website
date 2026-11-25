// Snapshot the running shop into plain files for GitHub Pages (a look-only preview: no bag, checkout or admin).
//   node tools/export-static.mjs [outDir] [basePath]
//   defaults: ../site-preview  /alora-website      (dev server must be running on :8080)
import { mkdir, writeFile, cp, rm } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const OUT = resolve(process.argv[2] || join(ROOT, '..', 'site-preview'));
const BASE = (process.argv[3] ?? '/alora-website').replace(/\/$/, '');
const SRC = process.env.SRC || 'http://localhost:8080';
// Pages that need the PHP server (sessions, forms, payments) are left out of the preview.
const SKIP = /^\/(admin|account|order|payfast|bag|checkout)\//;
const TOP = /(assets|media|product|product-category|shop|about|contact|personalised|terms|privacy|bag|checkout|account|admin|order)\//;

// Root-relative addresses ("/shop/") become "/alora-website/shop/" because Pages serves the repo under a sub-path.
const rebase = (s) => s
  .replace(new RegExp(`([\\s"'(,\`=])\\/${TOP.source}`, 'g'), `$1${BASE}/$2/`)
  .replace(/(href|action)="\/"/g, `$1="${BASE}/"`);

const BANNER = `<div style="position:sticky;top:0;z-index:9999;background:#1a1612;color:#e8d3a8;font:500 13px/1.4 Jost,system-ui,sans-serif;text-align:center;padding:8px 12px">Preview copy for review: browsing only. Bag, checkout and accounts are switched off.</div>`;

const seen = new Set(['/']);
const queue = ['/'];
let pages = 0;
while (queue.length) {
  const path = queue.shift();
  const res = await fetch(SRC + path, { redirect: 'manual' });
  if (res.status !== 200 || !(res.headers.get('content-type') || '').includes('text/html')) { console.log('skip', res.status, path); continue; }
  let html = await res.text();
  for (const m of html.matchAll(/href="(\/[^"#?]*)/g)) {
    const p = m[1];
    if (!p.endsWith('/') || SKIP.test(p) || seen.has(p) || p.startsWith('/assets/') || p.startsWith('/media/')) continue;
    seen.add(p); queue.push(p);
  }
  // Shop pages 2, 3, … ("?page=2") become plain folders ("page/2/"), since Pages cannot read query strings.
  for (const m of html.matchAll(/href="(\/[^"#?]*\/)\?page=(\d+)"/g)) {
    const p = `${m[1]}?page=${m[2]}`;
    if (!seen.has(p)) { seen.add(p); queue.push(p); }
  }
  html = html.replace(/href="(\/[^"#?]*\/)\?page=(\d+)"/g, 'href="$1page/$2/"').replace(/href="(\/[^"#?]*\/)\?"/g, 'href="$1"');
  html = rebase(html).replace(/<body([^>]*)>/, `<body$1>${BANNER}`);
  const file = join(OUT, decodeURIComponent(path.replace(/\?page=(\d+)$/, 'page/$1/')), 'index.html');
  await mkdir(dirname(file), { recursive: true });
  await writeFile(file, html);
  pages++;
}

// The 404 page, so wrong links still look like the shop.
const nf = await fetch(SRC + '/this-page-does-not-exist/');
await writeFile(join(OUT, '404.html'), rebase(await nf.text()).replace(/<body([^>]*)>/, `<body$1>${BANNER}`));

// Static files straight from public/, with their own root-relative paths rebased.
for (const d of ['assets', 'media']) await cp(join(ROOT, 'public', d), join(OUT, d), { recursive: true });
for (const f of ['assets/css/site.css', 'assets/js/site.js']) {
  const { readFile } = await import('node:fs/promises');
  await writeFile(join(OUT, f), rebase(await readFile(join(OUT, f), 'utf8')));
}
await writeFile(join(OUT, '.nojekyll'), '');
console.log(`${pages} pages -> ${OUT} (base ${BASE})`);
