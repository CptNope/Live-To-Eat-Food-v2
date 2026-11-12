"""Builds the share-card assets in ltef-core/assets/cards: TTF fonts GD can use, and PNGs of the
wordmark and stickers (GD can't read SVG or WOFF2). Run after changing the brand fonts or stickers:
    python3 wordpress/tools/build-cards.py
Needs fontTools + brotli, Pillow and Playwright (Chromium)."""
import os, subprocess, json, glob
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, 'live-to-eat-food', 'assets')
OUT = os.path.join(ROOT, 'ltef-core', 'assets', 'cards')

for src, name, axes in [
    ('bricolage-grotesque', 'bricolage-800', {'wght': 800, 'wdth': 100}),
    ('figtree', 'figtree-500', {'wght': 500}),
    ('figtree', 'figtree-700', {'wght': 700}),
    ('shantell-sans', 'shantell-700', {'wght': 700}),
]:
    f = TTFont(os.path.join(THEME, 'fonts', src + '.woff2'))
    f.flavor = None
    if 'fvar' in f:
        f = instancer.instantiateVariableFont(f, axes)
    f.save(os.path.join(OUT, 'fonts', name + '.ttf'))

svgs = {'wordmark': os.path.join(THEME, 'img', 'logo', 'ltef-wordmark-line.svg')}
for s in glob.glob(os.path.join(THEME, 'img', 'stickers', '*.svg')):
    n = os.path.basename(s)[:-4]
    if not n.startswith('roulette'):
        svgs['stickers/' + n] = s
job = [{'src': v, 'out': os.path.join(OUT, k + '.png'), 'w': 900 if k == 'wordmark' else 560} for k, v in svgs.items()]
js = """
const { chromium } = require('playwright'); const fs = require('fs');
(async () => { const b = await chromium.launch(); const p = await b.newPage();
  for (const j of JSON.parse(process.argv[1])) {
    const svg = fs.readFileSync(j.src, 'utf8');
    await p.setContent(`<html><body style="margin:0;background:transparent"><div id="x" style="width:${j.w}px;display:inline-block;line-height:0">${svg.replace('<svg', '<svg style="width:100%;height:auto;display:block"')}</div></body></html>`);
    await p.locator('#x').screenshot({ path: j.out, omitBackground: true });
  }
  await b.close(); })();
"""
subprocess.run(['node', '-e', js, json.dumps(job)], check=True)
for j in job:  # smaller files, same look
    im = Image.open(j['out']).convert('RGBA')
    im.quantize(colors=128, method=Image.Quantize.FASTOCTREE).save(j['out'], optimize=True)
print('built', len(job), 'images and 4 fonts')
