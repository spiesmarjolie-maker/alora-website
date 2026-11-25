"""Make the film cut-outs for approved ChatGPT photos: storage/enhanced/chatgpt/{stem}.png -> {stem}-cut.png (transparent).
  python -I tools/cut-chatgpt.py 2715 2714 ...
"""
import sys
from pathlib import Path
from PIL import Image
from rembg import new_session, remove

DIR = Path(__file__).resolve().parent.parent / 'storage' / 'enhanced' / 'chatgpt'
session = new_session('isnet-general-use')
for stem in sys.argv[1:]:
    img = Image.open(DIR / f'{stem}.png').convert('RGB')
    cut = remove(img, session=session).convert('RGBA')
    box = cut.getchannel('A').point(lambda a: 255 if a > 8 else 0).getbbox()
    if box:
        cut = cut.crop(box)
    cut.save(DIR / f'{stem}-cut.png')
    print(stem, cut.size)
