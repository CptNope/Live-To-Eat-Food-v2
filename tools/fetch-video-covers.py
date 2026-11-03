#!/usr/bin/env python3
"""Swap the drawn video stills for real cuts from Tina's TikToks.

Run from the repo root on a computer with normal internet access:

    python3 tools/fetch-video-covers.py

For every TikTok linked on the example site it asks TikTok's public oEmbed endpoint for the video's
cover image, saves it as assets/photos/videos/<video id>.webp (540 px wide), then points the site's
video stills at it. Videos that already have a cover are skipped, so it is safe to run again after
Tina posts. Needs Python 3.8+; uses Pillow to make WebP files if it's installed (pip install pillow),
and keeps the JPEG otherwise.

Covers come from Tina's own public posts. If she'd rather pick the frame herself, drop her image in
assets/photos/videos/ with the video id as the file name and run this again: it uses what's there.
"""
import glob, io, json, os, re, sys, urllib.parse, urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SITE = os.path.join(ROOT, 'site')
OUT = os.path.join(ROOT, 'assets', 'photos', 'videos')
UA = 'LiveToEatFood-site-builder/1.0'


def video_urls():
    urls = {}
    for f in glob.glob(os.path.join(SITE, '*.html')):
        for url in re.findall(r'href="(https://www\.tiktok\.com/@[^/"]+/video/(\d+))"', open(f, encoding='utf-8').read()):
            urls[url[1]] = url[0]
    return urls


def fetch(url):
    req = urllib.request.Request(url, headers={'User-Agent': UA})
    with urllib.request.urlopen(req, timeout=20) as r:
        return r.read()


def save_cover(vid, url):
    for ext in ('webp', 'jpg', 'png'):
        if os.path.exists(os.path.join(OUT, f'{vid}.{ext}')):
            return 'have it'
    meta = json.loads(fetch('https://www.tiktok.com/oembed?url=' + urllib.parse.quote(url, safe='')))
    thumb = meta.get('thumbnail_url')
    if not thumb:
        return 'no cover offered'
    raw = fetch(thumb)
    try:
        from PIL import Image
        im = Image.open(io.BytesIO(raw)).convert('RGB')
        if im.width > 540:
            im = im.resize((540, round(im.height * 540 / im.width)))
        im.save(os.path.join(OUT, f'{vid}.webp'), 'WEBP', quality=80)
    except ImportError:
        open(os.path.join(OUT, f'{vid}.jpg'), 'wb').write(raw)
    return 'saved'


def patch_site():
    """Point each video still at its cover. Leaves the drawing where there's no cover yet."""
    have = {os.path.splitext(f)[0]: f for f in os.listdir(OUT)}
    changed = 0
    for f in glob.glob(os.path.join(SITE, '*.html')):
        s = open(f, encoding='utf-8').read()
        def swap(m):
            vid = m.group(2)
            if vid not in have:
                return m.group(0)
            return (m.group(1).replace('class="tt"', 'class="tt tt--cover"')
                    + f'<img class="tt-cover" src="../assets/photos/videos/{have[vid]}" alt="" width="540" height="960" loading="lazy">')
        s2 = re.sub(r'(<div class="tt" data-tt="(\d+)"[^>]*>)<span class="tt-plate" aria-hidden="true"></span><img src="[^"]+" alt="" width="148" height="148">', swap, s)
        if s2 != s:
            open(f, 'w', encoding='utf-8').write(s2); changed += 1
    css = os.path.join(SITE, 'css', 'site.css')
    c = open(css, encoding='utf-8').read()
    if '.tt-cover' not in c:
        open(css, 'a', encoding='utf-8').write('\n.tt > img.tt-cover { grid-area: 1 / 1; width: 100%; height: 100%; margin: 0; object-fit: cover; transform: none; filter: none; }\n')
    return changed


def main():
    os.makedirs(OUT, exist_ok=True)
    urls = video_urls()
    print(f'{len(urls)} videos linked on the site')
    for vid, url in sorted(urls.items()):
        try:
            print(vid, save_cover(vid, url))
        except Exception as ex:  # one bad video shouldn't stop the rest
            print(vid, 'failed:', ex)
    print(f'{patch_site()} pages now show real covers')


if __name__ == '__main__':
    sys.exit(main())
