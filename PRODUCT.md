# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Static HTML + one CSS file, served by GitHub Pages from the repository root (`CptNope/Live-To-Eat-Food-v2`). No build step, no framework, JavaScript only for trivial enhancement. The production site that this brand book serves is a separate WordPress build (target launch late Nov 2026); the brand book ships a `theme.json`-ready token block for it, and `wordpress/` holds that build: a block theme plus a companion plugin for places, Woo-pons, events, jobs, ad spots, the forms and the review queue.

## Users

- **Primary (this repo):** Tina Vo, the creator, and the designer/developers building her WordPress site. They use the brand book to decide, approve, and build. A stranger (printer, sponsor, collaborator) should understand the brand from the overview page in about 60 seconds.
- **Primary (the brand's audience):** people in Worcester and Central Massachusetts who are deciding where to eat, often on a phone, often with someone else, and who already follow Tina (~25K Instagram, ~16K TikTok).

## Product Purpose

Live to Eat Food is a creator-led food discovery brand for Worcester & Central Mass. The website bottles ten years of Tina eating through the city on her own dime and makes it searchable: restaurant discovery, deals ("Woo-pons"), Restaurant Roulette (spin-the-wheel), an events calendar (food and non-food), daily deals, what's new, community photo pages (Your bites), giveaways, a food help page (pantries, free meals, Woo Fridges), and, for owners, a partners directory and restaurant jobs. Merch is on hold until Tina designs it. Woo-pons are planned as paid listings (two weeks free per business, then about $10 a week, to confirm), alongside online partner codes; the Tina page carries press and reader photos; Explore searches dishes, with owner-confirmed menus to come. Success for this repo: one visual system, approved by Tina, that the website and merchandise (tees, hoodies, hats, die-cut stickers) can both be built from.

## Positioning

The creator is the product. Every recommendation traces to a real meal Tina paid for. A directory can list restaurants; it cannot truthfully say "a real person you already trust ate here." Positioning line: "the friend who always knows where to eat." Promise: "every pick is a real meal."

## Operating Context

- Tina posts on Instagram and TikTok; the site extends that presence, it doesn't replace it.
- Paid things exist (Woo-pons, VIP Woo-pons, premium events, job posts, paid ads in Monthly Crumbs, paid visits, Enhanced listings) and are always labeled; they never touch verdicts or Tina's takes.
- No merch (decided Nov 2, 2026). The print piece is a business card that sends owners to the For restaurants page.

## Capabilities and Constraints

- Website features named in the brief: restaurant/place pages, discovery, Woo-pons deals, Restaurant Roulette, events calendar, community photo pages, giveaways.
- Monetization surfaces (from the monetization plan, Oct 2026, as changed Nov 2): four ad spots on the site (home, place-page rail, in-feed card, guides; unsold ones invite businesses to advertise) and paid ads in Monthly Crumbs, Woo-pons with a VIP tier, premium event listings, job posts, paid visits, Enhanced listings ($29–59/mo pilot, to confirm), 25 Charter merchants with a Featured decal, a Work with Tina media kit, an editorial and disclosure policy page, and a link-in-bio page.
- Typefaces must be freely available (Google Fonts) with fallbacks.
- Body text must meet WCAG AA; body text ≥ 16px; meaning never carried by color alone.
- Deliverable: Part A overview (`index.html`) + Part B brand book (`brand/index.html`), HTML + one CSS file.

## Brand Commitments

- Name: **Live to Eat Food**. Creator: Tina Vo. Region: Worcester & Central Massachusetts.
- Personality, in order: warm, playful, trustworthy.
- Client direction (binding): food-first, soft pastels led by yellows and greens, fun/inviting/modern, one system across web + print.
- Retired (client rejected): the triple-decker house / Worcester streetscape motif and the dark asphalt + mustard palette.
- Kept from the prior concept's rules (not its look): trust labeling (Tina ate here / Paid ad / Verified), verdicts in words instead of stars, specific-beats-enthusiastic voice, street-number specificity, Tina's real words only (never invented quotes), no generated likeness of Tina.
- "Woo-pons" and "Restaurant Roulette" are the client's feature names.

## Evidence on Hand

- Prior concept: https://github.com/CptNope/Live-to-Eat-Food (README documents palette, voice, trust labels, photography rules).
- Follower counts (~25K IG, ~16K TikTok) and "ten years" come from the client brief.
- No testimonials, advertiser names or sales numbers are on hand. Future work must not fabricate them: testimonial slots stay visibly empty until a business gives a real quote and its OK. Tina's own photos are in `assets/photos/tina`.
- The prior concept was prepared by Jeremy Anderson for Tina Vo as a proposal; this brand book is likewise a proposal until Tina adopts it.

## Product Principles

1. Trust is the brand: anything paid looks and reads different from anything Tina chose.
2. Food leads: dishes and ingredients are the hero of every surface.
3. Tina's voice is real or absent: her words are quoted, never written for her.
4. One system, two worlds: everything must work on a phone screen and on a cotton tee.
5. Specific beats enthusiastic.
6. Money, labeled: revenue is direct-sold in year one (paid ads in Monthly Crumbs, paid partnerships and paid visits, Enhanced listings, Woo-pons as paid listings (two weeks free, then $10 a week; VIP $100), premium event listings ($50 a month) and job posts ($100 a month)). Every paid surface is designed in from the start, labeled the same way everywhere, and absent when unsold. No programmatic display ads until about 25K monthly pageviews; no memberships, paywalls or ticketing yet. The contract line: "You're paying for content and reach, not for Tina's opinion."

## Accessibility & Inclusion

WCAG 2.2 AA for all information. Pastels are fields and fills only; text always sits on AA-or-better pairs. Respect `prefers-reduced-motion`.

## Decided at the launch meeting (Nov 2, 2026)

- Soft launch the first week of November. Tina checks submissions every night or every other night.
- Verdicts: two, *Favorite* (star) and *Don't miss* (target). Most places get neither.
- No merch. "Paid ad" replaces "Sponsored". The site's ad spots stay, and an unsold one says "Ad space open" with a way to advertise (Jeremy, Nov 2); Monthly Crumbs carries several paid ads per issue.
- Prices: Woo-pons 2 weeks free (once), then $10 a week; VIP Woo-pon $100, presented in Tina's monthly video, up to 10 a month; premium event listing $50 a month (listing stays free); job post $100 a month; paid visits at Tina's rate.
- Woo-pon submissions ask for exclusions, start and end dates and a contact phone; the form sits on the Woo-pons page right after "Using a Woo-pon".
- Roulette can spin all restaurants or only where Tina ate; a result from her list links her video.
- Monthly Crumbs carries Tina's month, her 3 best bites, readers' top 3 bites, every business she posted, events published and coming up, Woo-pons and paid ads.
- Business cards with a QR code bring businesses to the For restaurants page.
- Kept private, never on the public site: the don't-work-with list and any revenue split.
- After launch: a "looking for" page in user accounts. Before launch: an alias account for Tina to practice on.
