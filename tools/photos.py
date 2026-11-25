"""
Product photo clean-up (local, free, offline after the first model download).

For each product photo in storage/originals:
  1. turn it the right way up (phone EXIF rotation)
  2. upscale small photos with Real-ESRGAN (x4plus), then size to max 2000 px
  3. cut the product out with rembg (isnet-general-use)
  4. sanity-check the cut-out; if it looks wrong, keep the original background (only sharpened)
  5. shop version: product on a soft ivory studio backdrop with a gentle shadow, 1600x2000 JPEG
  6. film version: transparent cut-out PNG
Originals are never changed. Results: storage/enhanced/{shop,cutout}/ + storage/enhanced/manifest.json

  python -I tools/photos.py [--only 1275,1393] [--limit 10]
"""
import argparse, json, os, subprocess, sys, tempfile
from pathlib import Path
from PIL import Image, ImageFilter, ImageOps, ImageEnhance, ImageDraw

ROOT = Path(__file__).resolve().parent.parent
ORIG = ROOT / 'storage' / 'originals'
OUT = ROOT / 'storage' / 'enhanced'
ESRGAN = Path(os.environ['LOCALAPPDATA']) / 'alora-tools' / 'realesrgan' / 'realesrgan-ncnn-vulkan.exe'
IVORY_TOP, IVORY_BOTTOM = (248, 244, 236), (236, 227, 211)
W, H = 1600, 2000


def upscale(img: Image.Image) -> Image.Image:
    """Real-ESRGAN x4 for photos under 1400 px on the long side; otherwise unchanged."""
    if max(img.size) >= 1400 or not ESRGAN.exists():
        return img
    with tempfile.TemporaryDirectory() as d:
        src, dst = Path(d) / 'in.png', Path(d) / 'out.png'
        img.convert('RGB').save(src)
        r = subprocess.run([str(ESRGAN), '-i', str(src), '-o', str(dst), '-n', 'realesrgan-x4plus', '-s', '4'],
                           cwd=ESRGAN.parent, capture_output=True, timeout=600)
        if r.returncode != 0 or not dst.exists():
            return img
        return Image.open(dst).convert('RGB')


def backdrop() -> Image.Image:
    """Seamless ivory studio sweep: light at the top, a touch warmer at the bottom."""
    bg = Image.new('RGB', (W, H), IVORY_TOP)
    draw = ImageDraw.Draw(bg)
    for y in range(H):
        t = max(0.0, (y / H - 0.45) / 0.55)
        draw.line([(0, y), (W, y)], fill=tuple(round(a + (b - a) * t) for a, b in zip(IVORY_TOP, IVORY_BOTTOM)))
    return bg


def on_backdrop(cut: Image.Image) -> Image.Image:
    bbox = cut.getchannel('A').point(lambda a: 255 if a > 20 else 0).getbbox()
    cut = cut.crop(bbox)
    scale = min(W * 0.78 / cut.width, H * 0.74 / cut.height)
    cut = cut.resize((round(cut.width * scale), round(cut.height * scale)), Image.LANCZOS)
    x, y = (W - cut.width) // 2, round(H * 0.53 - cut.height / 2)
    bg = backdrop()
    # Soft contact shadow under the product.
    shadow = Image.new('L', (W, H), 0)
    sh = cut.getchannel('A').resize((cut.width, max(1, cut.height // 10)))
    shadow.paste(sh, (x, y + cut.height - sh.height // 2))
    shadow = shadow.filter(ImageFilter.GaussianBlur(28)).point(lambda a: a * 0.28)
    bg.paste(Image.new('RGB', (W, H), (120, 100, 70)), (0, 0), shadow)
    bg.paste(cut, (x, y), cut)
    return bg


def polish(img: Image.Image) -> Image.Image:
    img = ImageOps.autocontrast(img, cutoff=0.5, preserve_tone=True)
    img = ImageEnhance.Contrast(img).enhance(1.04)
    return img.filter(ImageFilter.UnsharpMask(radius=1.6, percent=60, threshold=3))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--only', default='')
    ap.add_argument('--limit', type=int, default=0)
    ap.add_argument('--files', default='', help='text file listing original file names, one per line')
    ap.add_argument('--keep-bg', default='', help='stems whose cut-out failed on review: keep the real background')
    a = ap.parse_args()
    keep = set(a.keep_bg.split(',')) if a.keep_bg else set()
    from rembg import new_session, remove
    session = new_session('isnet-general-use')

    names = [n.strip() for n in Path(a.files).read_text().split()] if a.files else sorted(
        p.name for p in ORIG.iterdir() if p.suffix.lower() in ('.jpg', '.jpeg', '.png', '.webp') and not p.name.startswith('cat-'))
    if a.only:
        names = [n for n in names if Path(n).stem in a.only.split(',')]
    if a.limit:
        names = names[:a.limit]
    for d in ('shop', 'cutout'):
        (OUT / d).mkdir(parents=True, exist_ok=True)
    manifest_path = OUT / 'manifest.json'
    manifest = json.loads(manifest_path.read_text()) if manifest_path.exists() else {}

    for i, name in enumerate(names, 1):
        stem = Path(name).stem
        # Resume: skip photos already done under the current rules (cut-outs only when cover < 0.45).
        done = manifest.get(name)
        if done and (OUT / 'shop' / f'{stem}.jpg').exists() and (not done['cutout'] or done['cover'] < 0.45) \
                and not (stem in keep and done['cutout']):
            continue
        img = ImageOps.exif_transpose(Image.open(ORIG / name)).convert('RGB')
        size_in = img.size
        img = upscale(img)
        img.thumbnail((2000, 2000), Image.LANCZOS)
        img = polish(img)
        cut = remove(img, session=session).convert('RGBA')
        alpha = cut.getchannel('A')
        cover = alpha.point(lambda a: 255 if a > 128 else 0).histogram()[255] / (alpha.width * alpha.height)
        # Flat-lay arrangements that fill the frame (cover > 0.45) keep their real background.
        ok = 0.04 < cover < 0.45 and stem not in keep
        if ok:
            shop = on_backdrop(cut)
            bbox = alpha.point(lambda a: 255 if a > 20 else 0).getbbox()
            film = cut.crop(bbox)
            film.thumbnail((1200, 1200), Image.LANCZOS)
            film.save(OUT / 'cutout' / f'{stem}.png', optimize=True)
        else:
            # Cut-out not trustworthy (a whole scene, or almost nothing found): keep the real photo, framed 4:5.
            shop = ImageOps.fit(img, (W, H), Image.LANCZOS, centering=(0.5, 0.5))
            (OUT / 'cutout' / f'{stem}.png').unlink(missing_ok=True)
        shop.save(OUT / 'shop' / f'{stem}.jpg', quality=92, optimize=True)
        manifest[name] = {'in': size_in, 'upscaled': max(size_in) < 1400, 'cutout': ok, 'cover': round(cover, 3)}
        manifest_path.write_text(json.dumps(manifest, indent=1))
        print(f'{i}/{len(names)} {name} {size_in} cover={cover:.2f} {"cut-out" if ok else "kept background"}', flush=True)


if __name__ == '__main__':
    sys.exit(main())
