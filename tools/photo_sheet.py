"""Before/after sheet: original | shop version (ivory) | film cut-out (on black).
   python -I tools/photo_sheet.py OUT.png name1.jpg name2.jpg ..."""
import sys
from pathlib import Path
from PIL import Image, ImageOps, ImageDraw

ROOT = Path(__file__).resolve().parent.parent
E = ROOT / 'storage' / 'enhanced'
CELL = 360


def cell(img, bg=(255, 255, 255)):
    c = Image.new('RGB', (CELL, int(CELL * 1.25)), bg)
    img = img.copy()
    img.thumbnail((CELL - 16, int(CELL * 1.25) - 16))
    c.paste(img, ((c.width - img.width) // 2, (c.height - img.height) // 2), img if img.mode == 'RGBA' else None)
    return c


out, names = sys.argv[1], sys.argv[2:]
rows = []
for n in names:
    stem = Path(n).stem
    orig = ImageOps.exif_transpose(Image.open(ROOT / 'storage' / 'originals' / n)).convert('RGB')
    shop = Image.open(E / 'shop' / f'{stem}.jpg').convert('RGB')
    cut_p = E / 'cutout' / f'{stem}.png'
    cut = Image.open(cut_p).convert('RGBA') if cut_p.exists() else None
    rows.append([cell(orig), cell(shop), cell(cut, (0, 0, 0)) if cut else cell(Image.new('RGB', (10, 10), (40, 40, 40)), (40, 40, 40))])
sheet = Image.new('RGB', (CELL * 3 + 8, (int(CELL * 1.25) + 4) * len(rows)), (200, 200, 200))
for r, row in enumerate(rows):
    for c, im in enumerate(row):
        sheet.paste(im, (c * (CELL + 4), r * (int(CELL * 1.25) + 4)))
sheet.save(out)
