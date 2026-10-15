---
name: Live to Eat Food
description: A food-first pastel sticker-sheet identity for Tina Vo's Worcester and Central Mass food guide, built for screens and cotton alike.
colors:
  butter: "#FFF6CF"
  lemon: "#FFE586"
  yolk: "#FFC53D"
  pistachio: "#DCEFC8"
  matcha: "#B3DA94"
  peach: "#FFD8C2"
  vinyl: "#FFFFFF"
  oat: "#EFEADB"
  oat-hatch: "#E4DECB"
  basil: "#1E3B2A"
  olive: "#46583E"
  leaf: "#2B6436"
  tomato: "#B3311A"
  chili: "#EE8467"
  latte: "#E8C9A3"
typography:
  display:
    fontFamily: "Bagel Fat One, Arial Rounded MT Bold, Nunito, system-ui, sans-serif"
    fontSize: "clamp(2.75rem, 7.4vw, 6rem)"
    fontWeight: 400
    lineHeight: 0.95
    letterSpacing: "-0.01em"
  headline:
    fontFamily: "Bagel Fat One, Arial Rounded MT Bold, Nunito, system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 5vw, 4rem)"
    fontWeight: 400
    lineHeight: 1
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Bagel Fat One, Arial Rounded MT Bold, Nunito, system-ui, sans-serif"
    fontSize: "clamp(1.625rem, 3vw, 2.25rem)"
    fontWeight: 400
    lineHeight: 1.08
    letterSpacing: "-0.01em"
  title-sm:
    fontFamily: "Bagel Fat One, Arial Rounded MT Bold, Nunito, system-ui, sans-serif"
    fontSize: "1.375rem"
    fontWeight: 400
    lineHeight: 1.15
    letterSpacing: "-0.01em"
  lede:
    fontFamily: "Figtree, Segoe UI, system-ui, -apple-system, sans-serif"
    fontSize: "clamp(1.1875rem, 1.6vw, 1.375rem)"
    fontWeight: 400
    lineHeight: 1.5
  body:
    fontFamily: "Figtree, Segoe UI, system-ui, -apple-system, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.6
    fontFeature: "\"ss01\""
  label:
    fontFamily: "Bricolage Grotesque, Figtree, Segoe UI, system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 700
    lineHeight: 1
  meta:
    fontFamily: "Bricolage Grotesque, Figtree, Segoe UI, system-ui, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 600
    lineHeight: 1.4
    fontVariation: "\"wdth\" 92"
  tag:
    fontFamily: "Bricolage Grotesque, Figtree, Segoe UI, system-ui, sans-serif"
    fontSize: "0.8125rem"
    fontWeight: 800
    lineHeight: 1.3
  body-sm:
    fontFamily: "Figtree, Segoe UI, system-ui, -apple-system, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.55
  small:
    fontFamily: "Bricolage Grotesque, Figtree, Segoe UI, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 600
    lineHeight: 1.4
  slug:
    fontFamily: "Bricolage Grotesque, Figtree, Segoe UI, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "0.04em"
  mono:
    fontFamily: "ui-monospace, SF Mono, Cascadia Mono, Menlo, Consolas, monospace"
    fontSize: "0.875rem"
    fontWeight: 500
    lineHeight: 1.6
  hand:
    fontFamily: "Shantell Sans, Comic Neue, Segoe Print, cursive"
    fontSize: "1.0625rem"
    fontWeight: 650
    lineHeight: 1.2
rounded:
  sheet: "36px"
  sheet-phone: "26px"
  item: "30px"
  sticker: "24px"
  card: "20px"
  chip: "16px"
  inset: "14px"
  frame: "22px"
  ticket: "18px"
  tag: "12px"
  focus: "8px"
  phone-body: "44px"
  phone-screen: "34px"
  sponsored: "6px"
  pill: "999px"
spacing:
  gutter: "clamp(16px, 4vw, 56px)"
  sheet-margin: "14px clamp(8px, 1.4vw, 18px)"
  sheet-pad-top: "clamp(56px, 8vw, 112px)"
  sheet-pad-bottom: "clamp(72px, 9vw, 120px)"
  head-gap: "clamp(32px, 4vw, 56px)"
  grid-gap: "30px"
  actions-gap: "18px 22px"
  sticker-pad: "18px 22px"
  panel-pad: "26px 28px"
  container: "1240px"
components:
  button-primary:
    backgroundColor: "{colors.basil}"
    textColor: "{colors.vinyl}"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "0 26px"
    height: "52px"
  button-primary-hover:
    backgroundColor: "{colors.leaf}"
    textColor: "{colors.vinyl}"
  button-secondary:
    backgroundColor: "{colors.vinyl}"
    textColor: "{colors.basil}"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "0 26px"
    height: "52px"
  button-secondary-hover:
    backgroundColor: "{colors.lemon}"
    textColor: "{colors.basil}"
  button-small:
    rounded: "{rounded.pill}"
    padding: "0 18px"
    height: "44px"
  sticker:
    backgroundColor: "{colors.vinyl}"
    textColor: "{colors.basil}"
    rounded: "{rounded.sticker}"
    padding: "{spacing.sticker-pad}"
  panel:
    backgroundColor: "{colors.vinyl}"
    textColor: "{colors.basil}"
    rounded: "{rounded.sticker}"
    padding: "{spacing.panel-pad}"
  sheet:
    backgroundColor: "{colors.butter}"
    textColor: "{colors.basil}"
    rounded: "{rounded.sheet}"
  sheet-dark:
    backgroundColor: "{colors.basil}"
    textColor: "{colors.vinyl}"
    rounded: "{rounded.sheet}"
  nav-link:
    textColor: "{colors.basil}"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "0 16px"
    height: "44px"
  nav-link-hover:
    backgroundColor: "{colors.butter}"
  nav-link-current:
    backgroundColor: "{colors.vinyl}"
  label-tina:
    backgroundColor: "{colors.vinyl}"
    textColor: "{colors.tomato}"
    typography: "{typography.hand}"
    rounded: "{rounded.pill}"
    padding: "6px 14px"
    height: "36px"
  label-verified:
    backgroundColor: "{colors.leaf}"
    textColor: "{colors.vinyl}"
    rounded: "{rounded.pill}"
    padding: "6px 14px"
    height: "36px"
  label-sponsored:
    backgroundColor: "{colors.oat}"
    textColor: "{colors.basil}"
    rounded: "{rounded.sponsored}"
    padding: "6px 14px"
    height: "36px"
  label-enhanced:
    backgroundColor: "{colors.oat}"
    textColor: "{colors.basil}"
    rounded: "{rounded.sponsored}"
    padding: "6px 14px"
    height: "36px"
  sponsor-slot:
    backgroundColor: "{colors.oat}"
    textColor: "{colors.basil}"
    rounded: "{rounded.sponsored}"
    padding: "10px"
  signup:
    backgroundColor: "{colors.lemon}"
    textColor: "{colors.basil}"
    rounded: "{rounded.sticker}"
    padding: "26px 28px"
  label-deal:
    backgroundColor: "{colors.pistachio}"
    textColor: "{colors.basil}"
    rounded: "{rounded.pill}"
    padding: "6px 14px"
    height: "36px"
  chip-filter:
    backgroundColor: "{colors.vinyl}"
    textColor: "{colors.basil}"
    typography: "{typography.tag}"
    rounded: "{rounded.pill}"
    padding: "7px 12px"
  chip-filter-on:
    backgroundColor: "{colors.basil}"
    textColor: "{colors.vinyl}"
  coupon:
    backgroundColor: "{colors.pistachio}"
    textColor: "{colors.basil}"
    rounded: "18px"
    padding: "16px 18px"
  verdict-do:
    backgroundColor: "{colors.matcha}"
    textColor: "{colors.basil}"
    rounded: "{rounded.pill}"
  verdict-dont:
    backgroundColor: "{colors.basil}"
    textColor: "{colors.vinyl}"
    rounded: "{rounded.pill}"
---

# Design System: Live to Eat Food

## Overview

**Creative North Star: "The Sticker Sheet"**

Every mark, label and food illustration in this system is a die-cut sticker you could peel off a sheet and put on a laptop, a takeout bag or a hoodie. A page is a stack of rounded pastel backing sheets (butter, lemon, pistachio, peach, white vinyl, one dark basil sheet), each carrying the printer's evidence of a real sheet: faint registration marks in all four corners and a small slug line along the bottom edge naming the sheet. Content sits on those sheets as white vinyl stickers, each with a printed kiss-cut ring around it and a soft lift off the paper.

The personality runs warm, then playful, then trustworthy. Warmth comes from butter-and-lemon daylight and one deep basil ink that carries every word. Play comes from a chunky rounded display face, small deliberate tilts and a scatter of food stickers around (never over) content. Trust is carried by a strict label system: Tina's handwriting in tomato means she ate there and paid; sponsors get a hatched oat tag with the word "Sponsored" and no stickers at all.

Density is relaxed and generous: big sheet padding, 30px grid gaps, one field colour per sheet. Food is the only illustration subject. The retired Worcester streetscape, triple-decker motif and asphalt-and-mustard palette are confirmed rejections and do not return.

**Key Characteristics:**
- Rounded pastel backing sheets stacked down the page, each with corner registration marks and a bottom slug.
- White vinyl stickers with a printed kiss-cut ring and a soft basil-tinted lift.
- One ink: basil green carries all text and outlines; pastels are grounds and fills only.
- Four type voices with fixed jobs: Bagel Fat One shouts, Figtree reads, Bricolage Grotesque labels, Shantell Sans is Tina's hand.
- Flat-colour food stickers with a 4u basil outline and a 9u white die-cut border.
- One authored motion, the peel.

## Colors

Daylight pastels led by yellows and greens, held together by a single deep basil ink.

### Primary
- **Basil Ink** (`basil`): the brand's one ink. All body text, headings, outlines, the primary button, the dark closing sheet. Reaches AA on every pastel (9.8:1 on lemon, 11.3:1 on butter).
- **Lemon Sheet** (`lemon`): the signature field. Hero sheets, the merch hero, giveaways, the avatar disc, the page canvas behind the sheets and the browser theme colour.

### Secondary
- **Pistachio Sheet** (`pistachio`): the second field. Deals, guides, community, alternating sheets, the Woo-pon ticket and deal labels.
- **Matcha Fill** (`matcha`): small fills only: the trust stamp, "Do" badges, illustration greens.
- **Leaf Link** (`leaf`): links in running text (underlined, 2px), primary button hover, the Verified label fill.

### Tertiary
- **Tomato Hand** (`tomato`): Tina's handwriting only: her notes, verdicts and the stamp centre. Never a button, link, sale, error or ad.
- **Yolk** (`yolk`): the lemon rind in the wordmark, yolks, number badges, step dots, text selection. Never fills anything wider than a button.
- **Peach Blush** (`peach`): a warm accent field, at most one sheet per page; also the "Fail" chip fill.
- **Chili** (`chili`) and **Latte** (`latte`): illustration only (pepperoni, roulette pointer, bread, cones, coffee). Never carry text.

### Neutral
- **Butter Ground** (`butter`): the default sheet field and reading ground; also nav hover, table heads and the phone-screen ground.
- **White Vinyl** (`vinyl`): sticker faces, panels, cards, die-cut borders, the secondary button, the ring around the primary button.
- **Olive** (`olive`): secondary text, captions, meta, slugs, inactive tabs, dashed outlines (7.1:1 on butter).
- **Oat** (`oat`) with **Oat Hatch** (`oat-hatch`): paid surfaces only, always as a −45° stripe of the two, a basil outline and the word "Sponsored".

### Named Rules
**The One Ink Rule.** Text is basil (or olive for secondary, leaf for links, tomato for Tina's hand). White text appears only on basil or leaf. White or pale text on a pastel never ships: white on lemon is 1.2:1.

**The One Field Per Sheet Rule.** One field colour owns a whole sheet; pastels are never striped inside a section. Roughly 60% butter or vinyl, 25% one other pastel field, 10% basil, 5% yolk, tomato and illustration colour.

**The Tomato Means Tina Rule.** Tomato signals only that Tina wrote it. It never marks a sale, an alert, an error or an ad, and never sets body copy on a pastel.

## Typography

**Display Font:** Bagel Fat One (with Arial Rounded MT Bold, Nunito, system-ui)
**Body Font:** Figtree (with Segoe UI, system-ui, -apple-system)
**Label/Interface Font:** Bricolage Grotesque (with Figtree, system-ui), widths 85–100
**Hand Font:** Shantell Sans (with Comic Neue, Segoe Print, cursive)

**Character:** A fat, rounded bagel of a display face that reads like a sticker title, paired with an open, friendly reading sans; a squared grotesque handles buttons and facts, and an informal hand belongs to Tina alone. All four are self-hosted WOFF2 under the SIL Open Font License, trimmed to the Latin set and to the weights and widths the system uses (Figtree 400–700, Bricolage 400–800 at 85–100% width, Shantell 500–800).

Code samples (token blocks, theme.json) use the platform monospace stack (`ui-monospace, "SF Mono", "Cascadia Mono", Menlo, Consolas, monospace`) rather than a fifth webfont; it appears only in the build-tokens section.

### Hierarchy
- **Display** (400, 44–96px fluid, 0.95): one per page, the page's big promise or a place name.
- **Headline** (400, 36–64px fluid, 1.0): sheet (section) headings, set left in a two-column head with the lede beside them.
- **Title** (400, 26–36px fluid, 1.08): card titles, deal amounts, merch lines, the hero line under the wordmark.
- **Title small** (400, 22px, 1.15): place names in lists, small sticker titles. Bagel is never used below 20px.
- **Lede** (Figtree 400, 19–22px fluid, 1.5): sheet intros, max 40–50ch.
- **Body** (Figtree 400, 17px, 1.6; 16px floor on phones): everything people read, max 68ch, `ss01` on.
- **Label** (Bricolage 700, 15–17px, 1.0): buttons, nav, tabs, trust labels.
- **Meta** (Bricolage 600, 15px, 1.4, width 92%, olive): cuisine, neighbourhood, price, distance, figcaptions.
- **Tag** (Bricolage 800, 13px, 1.3): chips, ratio tags, table heads, labels on stickers; never sentences.
- **Body small** (Figtree 400, 16px, 1.55): card copy, figcaptions and body on phones; never below 16px for reading text.
- **Small** (Bricolage 600, 14px, 1.4): facts lists, form hints and errors, the fine print on Woo-pons.
- **Slug** (Bricolage 600, 12px, 0.04em tracking, olive): the printer's slug along the bottom of each sheet. Decoration, never information.
- **Mono** (platform monospace, 14px, 1.6): token and code blocks only.
- **Hand** (Shantell Sans 650, 17px in labels and notes, up to 34px in specimens and pull lines, 1.2, tomato): one line in Tina's own words, never invented.

### Named Rules
**The Four Voices Rule.** Each face has one job and two roles never mix inside one element. Bagel shouts, Figtree reads, Bricolage labels, Shantell is Tina.

**The Sentence Case Rule.** Headings and buttons are sentence case. All caps live only in artwork (the stamp rim, tiny printed tags inside the Woo-pon ticket, decal and punch card), never in screen UI.

**The Tabular Facts Rule.** Prices, times, hex codes and tables use tabular numerals. Bagel tracks at −0.01em; everything else at 0.

## Layout

The page is a vertical stack of sheets on a lemon canvas. Each sheet is inset from the viewport (14px block, 8–18px inline fluid), takes the full width, and pads generously (56–112px top, 72–120px bottom, fluid gutter of 16–56px). Content inside a sheet sits in a 1240px wrap; the site bar and footer use 1420px. The bottom padding leaves room for the slug.

Sheet heads are a two-column grid (headline left, lede right, aligned to the baseline end, 24px by 56px gaps) that collapses to one column under 860px. Content grids use 30px gaps as the default rhythm (direction strips, specimens, panels, do/don't pairs); swatch and food grids use `auto-fill` minmax tracks. Merch uses a 12-column rack with items spanning 4, 6, 8 or 12.

Sticker placement is part of layout: up to 10 food stickers scattered around the desktop hero, 3 on a phone screen, 0–2 inside content sheets, always on the field around content, never over text, prices, labels or photos. Adjacent grid stickers alternate small tilts (about ±0.6° to ±1.6°) and sometimes a 14–28px vertical offset so a row reads as stuck on by hand.

The brand book uses a 260px sticky contents rail beside the sheets; under 1100px it becomes a horizontally scrolling pill row. Breakpoints are 1100px (rail collapses, four-up becomes two-up), 860px (two-column heads and pairs stack) and 600px (single column, sheet radius drops to 26px, body to 16px).

## Elevation & Depth

Depth is physical and shallow: paper sheets lifted barely off a table, stickers lifted a little more off the sheet. Every shadow is basil-tinted, never neutral black (except on the dark sheet), and pairs a 1px contact shadow with a soft negative-spread drop. The defining depth device is not a shadow at all: the kiss-cut, a printed 1.5px ring at 16% basil sitting 5px outside each sticker, drawn by stacking a field-coloured spread and a faint ring in `box-shadow`.

### Shadow Vocabulary
- **Sheet** (`box-shadow: 0 1px 0 rgb(30 59 42 / .06), 0 14px 30px -20px rgb(30 59 42 / .4)`): every backing sheet.
- **Lift** (`box-shadow: 0 1px 1px rgb(30 59 42 / .10), 0 8px 18px -8px rgb(30 59 42 / .32)`): stickers, panels, buttons, current nav item, phone UI pieces.
- **Lift high** (`box-shadow: 0 2px 2px rgb(30 59 42 / .10), 0 18px 30px -12px rgb(30 59 42 / .38)`): peeled (hovered) link stickers and the phone mockup frame.
- **Kiss-cut ring** (`box-shadow: 0 0 0 5px var(--field), 0 0 0 6.5px rgb(30 59 42 / .16)`): prepended to Lift on every sticker, panel, table wrap and do/don't figure; merch items use 6px/7.5px.
- **Food sticker drop** (`filter: drop-shadow(0 1px 1px rgb(30 59 42 / .12)) drop-shadow(0 6px 8px rgb(30 59 42 / .18))`): SVG food stickers, which carry their own white die-cut border.

### Named Rules
**The Kiss-Cut Rule.** Every white sticker surface on a sheet gets the kiss-cut ring in the sheet's own field colour. A white box with only a drop shadow is not a sticker.

**The Peel Rule.** The only authored motion: on hover a sticker lifts 4px, turns 3° further and scales 1.03 while its shadow deepens (500ms, `cubic-bezier(.16, 1, .3, 1)`). The roulette wheel spin is the one functional exception. No scroll-triggered entrances; nothing is hidden at rest; `prefers-reduced-motion` removes all transitions.

## Shapes

Everything is soft and rounded, like die-cut vinyl: sheets at 36px (26px on phones), merch items 30px, stickers and panels 24px, cards 20px, swatch chips 16px, inner photo frames 14px, and pills for every button, nav link, chip and trust label. In between sit the 22px screenshot and stage frames, 18px Woo-pon tickets, 12px tags and disclosure lines, an 8px focus ring, and the phone mockups (44px body, 34px screen). The one deliberate square-ish corner is the Sponsored tag (6px), so paid content reads as a different object. Food stickers are freeform silhouettes with a thick white border following the shape; the trust stamp is a 28-scallop bottle cap; the Woo-pon is a ticket with radial notches cut into both sides and a dotted perforation. Inner ink outlines on UI pieces run 2–2.5px basil (inset), matching the 4u outline in the artwork.

## Components

### Buttons
Pill stickers: tactile, confident, rimmed in white vinyl.
- **Shape:** full pill (999px), 52px tall, 26px inline padding; small variant 44px tall, 18px padding, 15px text.
- **Primary:** basil fill, white Bricolage 700 at 17px, a 4px white vinyl rim plus a faint 1.5px ring outside it, then Lift.
- **Hover / Focus:** fills to leaf, rises 2px and tilts −1° (400ms ease). Focus everywhere is a 3px basil outline at 3px offset with a 6px white halo.
- **Secondary:** white vinyl fill, basil text, 2.5px inset basil outline; hover fills lemon. On the dark sheet both actions use the secondary style.
- Icons are 20px inline stroke SVGs (2.1–2.6 stroke, round caps) that inherit `currentColor`.
- **States:** hover always sets its own text colour, so a page's link hover can't recolour a button. Disabled stays put on hover at 45–55% opacity. While sending, the button is disabled with `aria-busy="true"` so nothing goes twice. A pressed toggle ("I've been here") fills matcha with a check.
- **Long labels** wrap onto two centred lines at 1.15 line height instead of overflowing.

### Chips
- **Style:** filter chips are white pills with a 1.5px inset ring at 20% basil, Bricolage 700 at 13px; word chips are pistachio pills.
- **State:** selected chips flip to basil fill with white text and drop the ring. Pass/Fail and Do/Don't badges always pair an icon with a word.

### Cards / Containers
- **Corner Style:** stickers and panels 24px; app cards 20px; merch items 30px.
- **Background:** white vinyl on a pastel sheet.
- **Shadow Strategy:** kiss-cut ring plus Lift (see Elevation & Depth).
- **Border:** none; outlines are inset shadows.
- **Internal Padding:** stickers 18–22px, panels 26–28px, cards 10px around a 14px-radius photo frame.

### Inputs / Fields
- **Style:** search is a white pill with a 2px inset basil outline, Bricolage 600 at 15px in olive, an 18px search icon.
- **Focus:** the global focus ring (3px basil outline, 6px white halo).
- **Error:** basil text with an icon on a peach field plus a plain-words explanation; never tomato.
- **Form fields:** label, hint, input, error, in that order. Inputs are 48px tall with 16px text so phones don't zoom on focus. Hints and errors are tied to the input with `aria-describedby`; an invalid input turns dashed with a peach halo, so the state never rests on colour alone. Errors say what to fix and keep what was typed.
- **Load errors:** say what happened, keep filters and the map in place, and offer one Try again button. Empty results name the filters that are on and offer the one change that brings places back.

### Navigation
- **Site bar:** the one-line wordmark at left (168–230px), then five pill links in Bricolage 700 at 15px, 44px tall (Explore, Guides, Woo-pons, Roulette, Events), a **More** pill that opens a white 20px-radius menu with the rest (Your plates, Giveaways, Tina, Shop, How it works, For restaurants), and one primary button, Get Sunday's list. Hover fills butter; the current page becomes a white sticker with Lift, and More takes that look when the current page is inside it. Under 1080px Events moves into More. Under 960px the links and button fold into a **Menu** pill that opens a grouped drawer (Find food · Around the table · Live to Eat Food) with the button at its foot. Menus close on Escape, an outside tap, or when another opens.
- **Footer:** the same seven links on every page (How it works, Editorial policy, For restaurants, Work with Tina, About Tina, Sunday list, Shop), after a one-line promise that suits the page. The editorial policy link is never dropped.
- **Book contents:** a sticky rail of numbered pills, the number set in Bagel in leaf; the active section turns into a white lifted sticker. Under 1100px it becomes a horizontal scroller of butter pills.
- **Phone tab bar:** under 960px, a white floating 24px-radius bar pinned to the bottom with four tabs (Explore, Guides, Spin, Woo-pons): 22px line icons over Bricolage 700 at 12px, 52px tall; inactive olive, the current tab on a lemon pill in basil. It hides while the Menu drawer is open, and pages pad their bottom so nothing sits under it.
- **Explore map:** a schematic, not a street map: one bubble per neighborhood, sized by its number of places and labelled with the count and name. Pressing a bubble filters the list to that neighborhood (pressing it again clears it); bubbles recount as other filters change and fade when empty.

### Trust Labels (signature)
Pill labels, 36px tall, icon plus word, each visually unlike the others so meaning never rests on colour:
- **Tina ate here:** white pill, 2.5px inset tomato outline, Shantell Sans 700 in tomato, always with the visit month.
- **Verified business:** leaf fill, white Bricolage, shield icon.
- **Tina hasn't been yet:** transparent with a 2px dashed olive border.
- **Sponsored:** 6px-radius tag, oat/oat-hatch −45° stripes, 2px inset basil outline, dollar icon.
- **Paid partnership, Enhanced listing, Charter merchant:** the same hatched tag with their own word and icon (dollar, plus-in-square, storefront). Everything paid shares this one look.
- **Referral link:** transparent, 1.5px basil outline, weight 600, link icon.
- **Woo-pon:** pistachio fill, 2px basil outline, ticket icon.

### Woo-pon Ticket (signature)
A pistachio coupon with a 2px inset basil outline, 18px radius and 11px semicircular notches masked out of both sides; the amount in Bagel at 28px, the place in Bricolage 700, fine print under a dotted rule. The sponsored variant swaps the fill for the oat hatch. The code is printed on every ticket. "Show at the counter" opens that ticket full screen (above the tab bar, with a Close button and Escape), sized for staff to read; "Staff: mark as used" asks first ("Yes, it's used" / "Not yet") and then shows "Used today at …" with an Undo.
- **Details on the ticket:** the code in a dashed white box (Bagel, tabular figures), then a two-row list, *Runs* and *Excludes*, then the conditions under a dotted cut line with a small scissors mark. Nothing hides behind a tap.
- **Redeeming:** each live ticket ends with one "Show at the counter" button. It opens that ticket full screen: the ticket large with "Tina ate here", the code on white in Bagel with tabular figures and the date it's valid, then "Staff: mark as used", which confirms first and can be undone.
- **Used and ended:** oat field, olive text and a dashed label in words ("Used Oct 9", "Ended Oct 1"). Never faded with opacity, which drops text below AA.
- **Actions vs. links:** things that do something (Get, Save, RSVP, Add to bag, Enter) are buttons; things that go somewhere are links.

### Place page (signature)
One template for every place, free or Enhanced. **Her post leads.** When Tina has posted, the hero puts her TikTok (still until pressed, one "Watch on TikTok" link) beside the label, name, meta, Get directions and Save, then what she ordered, her line in her hand (the caption names the month, not another link), and one quiet line: a "Verdict coming" pill and "Tina hasn't picked one of her five yet." Never a phrase chosen for her. On phones the order is name, actions, what she ordered and said, then the video. When she hasn't been, the hero is the facts, the actions and "Not yet · Tina hasn't posted from here. Think she should go?" (linking Suggest a spot), with a food sticker as decoration: no photo slot, no stand-in words, and no sponsor rail on that page. Then Before you go: one panel, the listing facts beside "Worth knowing", with a sponsor (when one is sold, and only on pages with her post) in a narrow rail to the right (hours say "Not checked yet. Call ahead." until they are), the neighborhood (titled "Tina's latest" when the area has fewer than two other places), then the Sunday list ("Liked this?" only above her post). Enhanced adds the "From the owner" sheet after her take and nothing else.

### Restaurant Roulette (signature)
The wheel spins under a pointer that never moves (`roulette-wheel.svg` under `roulette-pointer.svg`). When it stops, the result is dealt as a butter ticket, beside the wheel on desktop (the intro gives way and the choices fold behind "Adjust") and straight under Spin on phones. The ticket carries the Tina ate here label, the name, what Tina ordered, her line linked to the post, then Get directions and See the place; on phones it adds Spin again, which brings the wheel back into view. While the wheel turns, the result keeps its height so nothing jumps. The one motion: the ticket lands with a small tilt and the label presses on after it (none with reduced motion). It takes focus. On phones the order is title, wheel, Spin, the ticket, then the choices folded behind "Adjust".

### Guides
Each stop has one button (Get directions), a quiet "Tina's video" text link and an "I've been here" chip; no stop carries three buttons.

### Explore list
A sentence filter ("I want [craving] with [who] in [neighborhood]"), the neighborhood bubble map (sticky on desktop, a folded strip above the list on phones), rows that show what Tina ordered, 12 places at a time, and at most one Sponsored card, only after the fourth visible pick. An empty result names the filters that are on and offers the one change that brings places back. On phones a basil count pill ("52 places ↓") sits right under the filters, updates with them and jumps to the list; neighborhoods with nothing left are drawn as dashed vinyl bubbles at full strength, never faded below readable contrast. The search placeholder fits a 390px screen ("Name or food, like ramen") and its clear button is drawn in basil. First sheets on phones start 32px under the nav so the first decision clears the tab bar.

### Paid surfaces (signature)
Trust is the product, so paid things are drawn so they can't be mistaken for picks.
- **Sponsor slot:** a 6px-radius frame in the oat hatch with a 2px basil outline; a head row with the Sponsored tag and "Paid placement · How paid spots work" (linking the editorial policy); a white body with the sponsor's logo or photo on oat (labeled as theirs), the name in **Bricolage 800 at 20px, never Bagel**, one line in their words and one secondary button. Variants: *home* (one, under Tina's latest picks, at most 640px wide), *rail* (place pages, one direct-sold local sponsor, image 5:2), *feed* (one in Explore after the fourth place, laid out like a row but never numbered).
- **Empty means absent:** a slot nobody bought isn't rendered. No grey box, no "your ad here".
- **No programmatic display ads in v1:** no ad networks, rectangles, pop-ups or autoplay until about 25K monthly pageviews, and then only inside these rules.
- **Enhanced listing:** adds owner modules after Tina's take, each a white panel with a 2px basil outline headed "From the owner" (photos captioned "Owner's photo", story, menu highlights, booking links). It never changes her take, list order or guides, and free listings are never dimmed. An Enhanced page carries no sponsor rail.
- **Featured decal:** the paid window decal for Charter merchants and Enhanced listings: rounded square, hatched frame, "Featured on" + wordmark, "Charter merchant · year", a QR code and "Paid listing · Tina's reviews can't be bought". It never carries the stamp.
- **Two doors:** "Suggest a spot" (editorial, on How it works and Explore) and "Work with Tina" (commercial: sponsorship, partnerships, Enhanced, Woo-pon submissions, hosted-visit invitations) never share a form.
- **Owner pages:** For restaurants opens on three doors, nothing else: *Fix my listing* (free; one form for hours, menu, openings and events, with an optional claim box and every real place suggested as you type), *Ask Tina to visit* (goes to Suggest a spot) and *Advertise* (wears the sponsored hatch and a Paid label, goes to Work with Tina). Work with Tina has one audience line instead of empty stat tiles, three jump links, one card per thing on offer (Woo-pons and hosted visits included), grouped under three small headings (On the site, With Tina, For your restaurant) so no row holds more than three, and **one** Get in touch form; each card's "Ask about…" link picks its line in that form.

### Sunday list signup (signature)
Revenue infrastructure, not a footer field. A lemon block (basil on dark sheets) with a 2.5px basil outline: Bagel heading, the pitch "Worcester's best food deals and Tina's weekly picks, every Sunday.", one email field and a primary button, then a Bricolage 600 fine-print line. On home it comes once, after Tina's picks, Woo-pons and Roulette (help first, then ask), plus the header button. It also closes every place page and guide, has its own page, and sits second on the link-in-bio page. A mistyped email gets the peach field error in words ("That email is missing the @."), never the browser bubble. Once it's sent, the block becomes its answer in place: a basil check that presses on like a stamp, "You're on Sunday's list.", the date the next one lands and the address it goes to, "See a sample issue" and "Use a different email" (no motion with reduced motion). The weekly email follows one template: Tina's opener (hers or blank), three new finds, the Woo-pons with codes, one thing happening, then at most one labeled sponsor; the hatch falls back to flat oat in email clients.

### Sheet (signature)
The page section itself: field-coloured rounded sheet with four corner registration marks (16px crosshair-in-circle at 28% opacity, inverted on basil) and a slug line centred 22px from the bottom edge in Bricolage 600 at 12px, width 85%, +0.04em, olive (pistachio on basil), naming the sheet, e.g. "LTEF · Sheet 3 of 6 · Palette".

## Do's and Don'ts

### Do:
- **Do** set basil text on every pastel field; it clears AA everywhere (9.8:1 on lemon, 11.3:1 on butter).
- **Do** give every white sticker surface the kiss-cut ring in its sheet's field colour plus the Lift shadow.
- **Do** let one field colour own each sheet, and use peach on one sheet per page at most.
- **Do** keep earned and paid visibly different: hand and tomato for Tina, hatch, oat and the word "Sponsored" for paid spots.
- **Do** pair every label, badge and verdict with a word and an icon.
- **Do** draw food stickers on a 120u square with a 4u basil outline, round joins, a 9u white die-cut border, two or three flat fills and at most one white highlight at about −30°.
- **Do** vary sticker tilts between −8° and +8°, never two neighbours at the same angle, and keep the wordmark within 3°.
- **Do** design every paid surface with the hatched frame and a label from day one, and leave it out of the page when nothing is sold.
- **Do** route editorial tips to "Suggest a spot" and anything commercial to "Work with Tina", never one form for both.
- **Do** date every "Tina ate here" label with month and year ("Oct 2026"), samples included.
- **Do** set apostrophes curly in names and in Tina's words (Leo’s, they’re); the data is converted at build.
- **Do** keep one name per filter: "Only places Tina’s been", on home and in Explore.
- **Do** call every maps link "Get directions", and name a link for where it goes ("See the place" when it opens our page).
- **Do** let focus rings follow the control's own shape: pills get pill rings, inline links a 6px corner.
- **Do** keep sheets off the lemon page color: a hero or signup sheet is butter, vinyl, pistachio or peach, never lemon on lemon.
- **Don't** put a paid stop in a guide with fewer than four real stops.
- **Don't** repeat the promise. Each page says it once, in the footer line beside the editorial policy link; paid things keep their labels, and a sponsor's line is in the sponsor's words, not ours about them.
- **Don't** let Verified shout: it is an outlined leaf label, quieter than "Tina ate here".

### Don't:
- **Don't** set white or pale text on a pastel; white on lemon is 1.2:1.
- **Don't** use tomato for anything but Tina's hand: not buttons, links, sales, errors or alerts.
- **Don't** put the stamp, Tina's handwriting or a quote in her voice on a sponsored placement, and give sponsored modules no stickers.
- **Don't** rate with stars or numbers; use Tina's verdicts in words.
- **Don't** place stickers over food photos, prices, labels or body text; they go around content, on the field.
- **Do** keep the wordmark's food o's as a drawn pair: donut + lemon by default; pizza + ramen or the classic eggs for a guide, season or campaign. One pair per surface.
- **Don't** stretch, squash or recolour the wordmark, mix two o pairs, swap in other stickers for the o's or split its lines.
- **Don't** bring back houses, streetscapes, skylines or city clip-art, or the asphalt-and-mustard palette.
- **Don't** set Bagel Fat One below 20px or for paragraphs, or set screen UI in all caps.
- **Don't** add gradients, textures or shading to food stickers, or faces on food.
- **Don't** dress a paid placement as a pick: no Bagel names, stamp, handwriting, "Featured" carousel, star display or slot in a best-of list.
- **Don't** design programmatic display-ad blocks, paywalls, memberships or ticketing into v1, or leave nav slots for them.
- **Don't** invent audience numbers or rates; leave them "Coming soon" until measured.

## Disclosure: who paid

- **Tina paid**: `Tina ate here · [month]`; stamp and decal allowed.
- **Hosted** (restaurant invited Tina and covered the meal): `Tina ate here` + the Hosted label (peach fill, basil outline, envelope icon) and a disclosure line above the take. Verdicts stay hers. No stamp, no decal.
- **Paid partnership** (a brand paid Tina to post): `Paid partnership with [brand]` in the Sponsored hatch. No verdicts, no stamp; never in rankings, guides, Roulette or the Sunday list.
- **Sponsored** (paid space on the site or in the Sunday email): hatched oat, brand voice, never in Tina's hand.
- **Enhanced listing / Charter merchant** (the owner pays for extra modules on their page): hatched label, owner modules headed "From the owner" after Tina's take, the Featured decal (never the stamp). Ranking, guides and her take are untouched.
- **The contract line**: "You're paying for content and reach, not for Tina's opinion." It appears in the media kit, sponsor agreements and the editorial policy.
