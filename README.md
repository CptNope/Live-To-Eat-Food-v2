# Live to Eat Food — Brand Book v2

A brand identity proposal for **Live to Eat Food**, Tina Vo's creator-led food guide to Worcester and Central Massachusetts. It replaces the first concept's triple-decker / asphalt-and-mustard direction with what Tina asked for: **food first, soft pastels led by yellows and greens, fun and modern, one system for the website and the merch.**

The idea: **the brand is a sticker sheet.** Every mark, label and food illustration is a die-cut sticker you could peel off and put on a laptop, a takeout bag or a hoodie. The two o's in "Food" are food too: a sushi roll and a bagel by default (Tina's pick), swappable for pizza + ramen or the classic sunny-side eggs.

| Page | File | What it is |
|---|---|---|
| Overview | [`index.html`](index.html) | The brand in 60 seconds: intro, direction, palette, type, marks |
| Brand book | [`brand/index.html`](brand/index.html) | Strategy, color, typography (with the display-font options), logo & marks (with the stamp options), motifs, voice, imagery, applications and the newer pages, don'ts, build tokens |
| Merch lookbook | [`merch/index.html`](merch/index.html) | On hold (Oct 2026): Tina will design the merch herself later. Concepts for tees, hoodie, hat, sticker pack, tote, pin, punch card, restaurant window decal, print specs |
| Launch questions | [`launch/index.html`](launch/index.html) | Every question and decision still open before launch, grouped (brand, facts to confirm, money, legal, build), with who decides each and what's already settled |
| Example site | [`site/index.html`](site/index.html) | 86 pages of the website built from the system: home, explore (filters and neighborhood map), 52 real place pages with Tina's TikToks, 5 neighborhood guides, Woo-pons, daily deals, what's new, Restaurant Roulette, events, your bites, giveaways, food help (pantries, free meals, Woo Fridges), Tina, how it works, for restaurants with a partners directory and restaurant jobs, plus the paid side kept visibly apart: Work with Tina (media kit), the editorial and disclosure policy, the Monthly Crumbs newsletter page and its issue and welcome emails, a sample Enhanced listing, a link-in-bio page a mock-up of Tina's private review queue (`site/review-queue.html`), Tina's brand picks (`site/tinas-picks.html`) and the on-hold shop (`site/shop.html`, linked only from the overview). Every page on the canvas is also a page on the site, and every site page is listed on the overview. Places, quotes and videos come from the first concept's data; deals, events, prizes and the hosted visit are samples |

**Tina's picks (Oct 19):** Outfit 700 for headings, the sushi roll + bagel o's, the plate stamp and a barcode on the Woo-pons ticket, now live everywhere. **Still open:** an Outfit-lettered wordmark (drawn as an option), Woo-pons pricing (two weeks free, then $10 a week, shown as to confirm) and the Monthly Crumbs name and cadence. The design canvas lays its boards out in site navigation order: the main bar, the More menu, the footer and newsletter, then pages opened from a list, phone screens, and what's waiting on Tina.

The same system is also saved as a Claude Design System (tokens, fonts, logos, stickers, merch mockups and component previews): <https://claude.ai/artifact/3FK3oCDgfLmRHArTGLcX7i> (private until shared).

## Publish on GitHub Pages

Settings → Pages → Source: **Deploy from a branch**, Branch: `main`, folder `/ (root)`. No build step. The site will be at `https://cptnope.github.io/Live-To-Eat-Food-v2/`.

## What's in the repo

```
index.html, brand/, merch/, launch/   the brand book pages and the launch questions (plain HTML)
site/                        the example website: 86 pages, site/css (tokens, components, layout), site/js/places.js (Roulette data), site/js/site.js (nav menus, Explore filters, TikTok player, Roulette, demo messages)
css/ltef.css                 the one stylesheet
js/ltef.js                   small enhancements only (copy buttons, roulette demo, TOC highlight)
fonts/                       Bagel Fat One, Figtree, Bricolage Grotesque, Shantell Sans (WOFF2, Latin subset, SIL OFL)
assets/logo/                 wordmark (stacked, sticker, one-line, white) × 3 o pairs, avatar, trust stamps, paid Featured decal — outlined SVG
assets/stickers/             24 food stickers + Woo-pons ticket + roulette wheel — SVG
assets/merch/                garment and product mockups — SVG
assets/site/                 screenshots of the example site for the overview — WebP
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
| Yolk | `#FFC53D` | Lemon rind, yolks, highlights (7.8:1) |
| Pistachio | `#DCEFC8` | Second field (10.0:1) |
| Matcha | `#B3DA94` | Stamp, fills (7.8:1) |
| Peach | `#FFD8C2` | Warm accent, sparingly |
| Basil | `#1E3B2A` | All text and outlines |
| Tomato | `#B3311A` | Tina's handwriting only (6.2:1 on white) |

## Status

A proposal for Tina Vo, October 2026. Nothing here is adopted until Tina says so. "Live to Eat Food", Tina's name, her words and her photos belong to her. Restaurant names, deals and dates in the application mockups are placeholders. Fonts are under the SIL Open Font License (licenses in `fonts/`). Stickers, marks and mockups are original SVG drawn for this book.
