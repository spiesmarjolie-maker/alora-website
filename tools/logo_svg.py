"""
Trace the Alora logo (brand/alora-logo-original.jpg, gold on black) into clean SVGs.

  python -I tools/logo_svg.py

One clean shape filled with a brushed-gold gradient (tone layers were tried: they looked blotchy).
Outputs in public/assets/img/:
  alora-logo.svg       gold, transparent background (for the black film / dark bands)
  alora-logo-mono.svg  one flat colour (currentColor), for ivory pages, print and favicons
"""
import re
from pathlib import Path
import vtracer
from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT.parent / 'brand' / 'alora-logo-original.jpg'
OUT = ROOT / 'public' / 'assets' / 'img'
SCALE = 2  # trace at 2x for smoother curves


def trace(mask: Image.Image) -> list[str]:
    """Binary mask (white = shape) -> list of SVG path 'd' strings, translated into place."""
    img = Image.new('RGBA', mask.size, (255, 255, 255, 255))
    img.paste((0, 0, 0, 255), (0, 0), mask)
    pixels = list(img.get_flattened_data())
    svg = vtracer.convert_pixels_to_svg(pixels, size=img.size, colormode='binary', mode='spline',
        filter_speckle=48,  # drops specks and pin-holes under ~7x7 px (at 2x)
        corner_threshold=60, length_threshold=4.0, splice_threshold=45, path_precision=1)
    paths = []
    for tag in re.findall(r'<path [^>]*/>', svg):
        d = re.search(r' d="([^"]+)"', tag).group(1)
        t = re.search(r'translate\(([-\d.]+),([-\d.]+)\)', tag)
        paths.append((d, float(t.group(1)) if t else 0.0, float(t.group(2)) if t else 0.0))
    return paths


def main():
    im = Image.open(SRC).convert('RGB')
    im = im.resize((im.width * SCALE, im.height * SCALE), Image.LANCZOS).filter(ImageFilter.GaussianBlur(1.2))
    lum = im.convert('L')
    gold = lum.point(lambda v: 255 if v > 70 else 0).filter(ImageFilter.MedianFilter(5))
    # Close the hairline nicks left by creases in the 3D shading (grow then shrink by 2 px).
    gold = gold.filter(ImageFilter.MaxFilter(5)).filter(ImageFilter.MinFilter(5))
    # Bounding box from the solid shapes only, so stray JPEG specks near the edge don't widen the frame.
    x0, y0, x1, y1 = gold.filter(ImageFilter.MinFilter(5)).getbbox()
    pad = 24
    vb = f'{x0 - pad} {y0 - pad} {x1 - x0 + 2 * pad} {y1 - y0 + 2 * pad}'

    def layer(mask, attrs):
        out = []
        for d, tx, ty in trace(mask):
            t = f' transform="translate({tx:g},{ty:g})"' if tx or ty else ''
            out.append(f'<path d="{d.strip()}"{t}/>')
        return f'<g {attrs}>' + ''.join(out) + '</g>'

    base = layer(gold, 'fill="url(#gold)" fill-rule="evenodd"')
    gold_svg = (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{vb}" role="img" aria-labelledby="t"><title id="t">Alora</title>'
        # Brushed gold: light catches the top left, a soft sheen across the middle, deeper gold at the bottom right.
        '<defs><linearGradient id="gold" x1="0" y1="0" x2="1" y2="1">'
        '<stop offset="0" stop-color="#f3dcaa"/><stop offset=".35" stop-color="#c9a46a"/>'
        '<stop offset=".55" stop-color="#ead09c"/><stop offset=".8" stop-color="#b88f55"/>'
        '<stop offset="1" stop-color="#8f6a38"/></linearGradient></defs>'
        + base + '</svg>')
    mono_svg = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{vb}" role="img" aria-labelledby="t"><title id="t">Alora</title>'
                + layer(gold, 'fill="currentColor" fill-rule="evenodd"') + '</svg>')
    (OUT / 'alora-logo.svg').write_text(gold_svg, encoding='utf-8')
    (OUT / 'alora-logo-mono.svg').write_text(mono_svg, encoding='utf-8')
    for n in ('alora-logo.svg', 'alora-logo-mono.svg'):
        print(n, round((OUT / n).stat().st_size / 1024), 'KB')


if __name__ == '__main__':
    main()
