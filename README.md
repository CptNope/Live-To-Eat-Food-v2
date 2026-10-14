# Live to Eat Food — Brand Book v2

A brand identity proposal for **Live to Eat Food**, Tina Vo's creator-led food guide to Worcester and Central Massachusetts. It replaces the first concept's triple-decker / asphalt-and-mustard direction with what Tina asked for: **food first, soft pastels led by yellows and greens, fun and modern, one system for the website and the merch.**

The idea: **the brand is a sticker sheet.** Every mark, label and food illustration is a die-cut sticker you could peel off and put on a laptop, a takeout bag or a hoodie. The two o's in "Food" are sunny-side eggs.

| Page | File | What it is |
|---|---|---|
| Overview | [`index.html`](index.html) | The brand in 60 seconds: intro, direction, palette, type, marks |
| Brand book | [`brand/index.html`](brand/index.html) | Strategy, color, typography, logo & marks, motifs, voice, imagery, applications, don'ts, build tokens |
| Merch lookbook | [`merch/index.html`](merch/index.html) | Tees, hoodie, hat, sticker pack, tote, pin, punch card, restaurant window decal, print specs |

The same system is also saved as a Claude Design System (tokens, fonts, logos, stickers, merch mockups and component previews): <https://claude.ai/artifact/3FK3oCDgfLmRHArTGLcX7i> (private until shared).

## Publish on GitHub Pages

Settings → Pages → Source: **Deploy from a branch**, Branch: `main`, folder `/ (root)`. No build step. The site will be at `https://cptnope.github.io/Live-To-Eat-Food-v2/`.

## What's in the repo

```
index.html, brand/, merch/   the three pages (plain HTML)
css/ltef.css                 the one stylesheet
js/ltef.js                   small enhancements only (copy buttons, roulette demo, TOC highlight)
fonts/                       Bagel Fat One, Figtree, Bricolage Grotesque, Shantell Sans (Latin subset, SIL OFL)
assets/logo/                 wordmark (stacked, sticker, one-line, white), avatar, trust stamps — outlined SVG
assets/stickers/             12 food stickers + Woo-pons ticket + roulette wheel — SVG
assets/merch/                garment and product mockups — SVG
tokens/theme.json            WordPress theme.json (v3) tokens
tokens/ltef-tokens.css       the same tokens as CSS custom properties
favicon.svg, apple-touch-icon.png
PRODUCT.md, DESIGN.md        product truth and design-system record
```

## Palette at a glance

| Token | Hex | Role |
|---|---|---|
| Butter | `#FFF6CF` | Page ground (basil text 11.3:1) |
| Lemon | `#FFE586` | Signature field (9.8:1) |
| Yolk | `#FFC53D` | The egg, highlights (7.8:1) |
| Pistachio | `#DCEFC8` | Second field (10.0:1) |
| Matcha | `#B3DA94` | Stamp, fills (7.8:1) |
| Peach | `#FFD8C2` | Warm accent, sparingly |
| Basil | `#1E3B2A` | All text and outlines |
| Tomato | `#B3311A` | Tina's handwriting only (6.2:1 on white) |

## Status

A proposal for Tina Vo, October 2026. Nothing here is adopted until Tina says so. "Live to Eat Food", Tina's name, her words and her photos belong to her. Restaurant names, deals and dates in the application mockups are placeholders. Fonts are under the SIL Open Font License (licenses in `fonts/`). Stickers, marks and mockups are original SVG drawn for this book.
