# Live to Eat Food for WordPress

The brand book and the example site, turned into a real WordPress site: a **block theme** (`live-to-eat-food/`) and a small **companion plugin** (`ltef-core/`). The theme is the look. The plugin holds the content and the forms, so switching themes later never loses a place, a Woo-pon or a submission.

Requires WordPress 6.6 or newer (tested on 6.8) and PHP 7.4 or newer.

## Install

1. Build the zips: `bash wordpress/build-zips.sh` (they land in `wordpress/dist/`, which isn't committed).
2. In WordPress: **Plugins → Add New → Upload Plugin** → `ltef-core.zip` → Activate.
3. **Appearance → Themes → Add New → Upload Theme** → `live-to-eat-food.zip` → Activate.
4. **Tools → Live to Eat Food setup**, then press the three buttons in order:
   - **Create the pages:** Home, Guides, Restaurant Roulette, Food help, Send something in, Work with Tina, For restaurants, How it works, Editorial policy, Monthly Crumbs, Tina, Daily deals and Your bites. It also sets Home as the front page, Guides as the posts page and permalinks to `/%postname%/`.
   - **Import Tina's places and videos:** every place from her TikToks back to May 2024 and her Instagram posts that never went to TikTok (305 as of November 2026: food, plus the shops, wellness spots and things to do she has featured), with her caption as her line, plus 282 restaurants she hasn't been to yet across Worcester County, MetroWest and the Lowell area (address, phone and website from current public listings; hours left for someone to check), the 5 guides, 48 round-ups of her event, trip and older Instagram posts (back to 2017), and the weekly deals she mentions in her videos (Daily deals), and her other videos from each place. It skips anything already on the site (but a "not yet" place she has since been to gets her video and line, and an existing round-up gets any of her videos it's missing), and any business whose name matches the private don't-work-with list, so press it again after each update to bring in what's new.
   - **Add samples:** labeled `[Sample]` Woo-pons, events and jobs, so the pages aren't empty while you look around. Delete them before launch. (The Lensmart partner code is real.)
5. **Review queue → Settings:** where new submissions are emailed, the prices, and the private don't-work-with list.

## What the plugin adds

| In the admin | What it's for | Public URL |
|---|---|---|
| Places | Every place: Tina's quote, video, date and verdict (Favorite or Don't miss), plus address, hours and "worth knowing" facts. Neighborhoods, cravings and "good with" are taxonomies. | `/explore/`, `/place/<name>/` |
| Woo-pons | Local deals and partner codes: free trial, weekly, VIP (with its flyer) or partner. They show only between their start and end dates. | `/woo-pons/` |
| Events | Out & About Worcester+, with the $50 premium listing flag. | `/events/` |
| Jobs | Restaurant job posts ($100 a month). | `/restaurant-jobs/` |
| Ads | Sold ad spots (home, place rail, feed, guide). An unsold spot shows "Ad space open" and links to Work with Tina. | none |
| Review queue | Everything people send in waits here: Approve, Edit first, or Decline with a private reason (and optionally add them to the don't-work-with list). Matches against that list are flagged. | none |
| Messages and signups | Contact messages, suggestions, photos, listing fixes and Monthly Crumbs signups. | none |

**Blocks** (in the inserter under *Live to Eat Food*): Tina's take, Tina's video, Before you go, Place card, Latest places, Explore, Woo-pons, Woo-pon ticket, Ad spot, Submission form, Events, Jobs, Restaurant Roulette and Verdicts. They render on the server, so they always show current content.

**Forms:** Woo-pon, event, job, suggest a spot, claim or fix a listing, special or new dish, photo with Tina, Monthly Crumbs signup and Work with Tina. Each has a honeypot, a time check, a rate limit and a nonce. A VIP Woo-pon can't be sent without a flyer (JPG, PNG or PDF, up to 10 MB). Nothing goes live until Tina approves it; signups are saved straight away and don't email her.

**Privacy:** contact details, the raw form fields, the don't-work-with flag and decline reasons are protected meta. They never reach the REST API or the site. The don't-work-with list lives only in Settings. Don't type names from it into pages, posts or this repo.

### More from Tina, and what kind of place

- **What kind of place:** Food & drink (the default), Shop, Wellness & beauty, Services, Things to do or Community. Only food & drink places go on Restaurant Roulette and Tina's latest picks; Explore has a "What" filter for the rest and shows 24 at a time with a Show more button (the filters search the whole list; without JavaScript every place shows), and their label reads "Tina went here".
- **More videos from here:** her other videos from the same place, one per line as `month | link | her caption`. They show small under "More from Tina here", newest first, each with her caption.
- **Tina's video (any link)** is a block for posts: a link (or several copies, separated by spaces), the month and her caption. The round-ups under *Out & about* use it.
- Captions are Tina's own words from the post, with emoji and @ signs taken out. Where a caption had a promo code or a date that has passed, only the first sentence is used.

### Where Tina's videos play from

Each place has Tina's video link (where she first posted it) and three optional copies: **YouTube**, **Instagram** and **Facebook**. The site plays the first one it has, in this order:

1. **YouTube**, in its privacy-enhanced player: blocked least by schools, offices and privacy tools.
2. **Instagram**
3. **Facebook** (the video has to be Public, and the link has to be the video's own: `facebook.com/reel/…` or `…/videos/…`; share links only get linked to)
4. **TikTok**: blocked most often.

None of them needs the visitor to have an account for a public video (checked signed out in November 2026). Under the player, "Not playing? Watch it on …" links to every copy. Nothing loads from any platform until someone presses play. To change the order, filter `ltef_video_priority`.

## Money (private)

**Money** in the admin menu is for Tina and Jeremy only. It's visible to administrators, and only the ones ticked under *Prices & split* once anyone is ticked. Nothing on it reaches the site, the REST API or search.

- **Overview:** income, expenses and profit for this month, last month, this year or all time; where the money goes; what each partner spent, is still owed, has earned, has been paid and is due now; a 12-month chart; a CSV download for the accountant.
- **Income:** one line per payment (what it's for, from whom, how it was paid). Stripe payments will add themselves once Stripe is connected.
- **Expenses:** one line per cost, with **who paid** (a partner or the business) and an optional receipt photo or PDF (up to 10 MB; a phone can take the photo right there). Receipts are stored outside the public web folder on Cloudways (`private_html/ltef-receipts`), or in a hidden folder elsewhere, and open only for someone signed in who can see Money.
- **Payouts:** money actually moved to a partner, so *Due now* stays right.
- **Prices & split:** the prices the site shows, the two partners' shares (entered by them; blank until they do), an optional reserve held back before dividing, and who can see the page.

**How it divides:** income, minus costs the business paid itself, first pays back money the partners spent personally, oldest expense first (an expense marked as a gift isn't paid back). Then the reserve, if any, is held back. Whatever's left is divided by the shares. Shares aren't calculated until they add up to 100%.

**Live prices:** prices on the site come from these settings through `[ltef_price key="woopon_weekly"]` (keys: `woopon_free`, `woopon_weekly`, `woopon_vip`, `vip_limit`, `event_premium`, `job_post`, `enhanced_low`, `enhanced_high`). Pages created before version 1.1 still hold typed-in prices: run **Tools → Live to Eat Food setup → Refresh the built-in pages** once after updating. The old text stays under each page's Revisions.

This isn't accounting software. Keep the CSV and the receipts for whoever does the taxes.

## The theme

- `theme.json` carries the brand tokens: the palette, Outfit / Figtree / Bricolage / Shantell (self-hosted, SIL OFL), spacing, and the 760 / 1240 widths.
- Block styles: **Sheet**, **Panel** and **Dashed** groups; **Alt** buttons; **Lede**, **Hand** and **Meta** paragraphs.
- Templates for the home page, pages, posts (guides), search, 404, and each of the plugin's content types. Two extra page templates: **No title** (the page's own pattern supplies the heading) and **Blank canvas**.
- Patterns: the header, footer and Monthly Crumbs signup, plus every page from the example site (Food help, Tina, Work with Tina, Send something in and the rest), all editable in the Site Editor.
- CSS: `assets/css/ltef.css` is built from `assets/css/src/` (site layout, the design system's components, page sections, then WordPress fixes). Edit the parts, then run `sh assets/css/build.sh`.

### Video covers

Tina's videos show a drawn still until someone presses play; nothing loads from TikTok before then. To show each TikTok's real cover instead, either set the place's featured image, or run `tools/fetch-video-covers.py` (from the repo root, somewhere TikTok is reachable) and copy `assets/photos/videos/*.webp` into the theme's `assets/photos/videos/`.

## Still to do before launch

- **An email service for Monthly Crumbs.** Signups are saved under *Messages and signups*. Connecting the service (and the welcome email) is still open on the launch questions page.
- **Real content.** Delete the `[Sample]` posts, add the real ads as they're sold, and check every place's hours.
- **A privacy page and a terms page.** The forms collect emails, phone numbers and photos.
- **Email delivery.** WordPress's mail needs an SMTP plugin on most hosts, or the review queue notifications won't arrive.

## Checked

On WordPress 6.8.3 (SQLite), from the zips, on a fresh install: every page returns 200 with no PHP warnings; every page and pattern opens in the editor with no invalid blocks; axe (WCAG 2.2 AA) reports no violations at 390 and 1280 px wide; no page scrolls sideways; the forms submit into the review queue, and Approve publishes.

## Daily deals

Weekly specials live under **Places → Daily deals**: pick the place, tick the days, write the deal in a few words, and say where it comes from (Tina's post, the place, or a reader checked with the place). The Daily deals page lists them Monday to Sunday with today marked, and each place's "Before you go" box shows its weekly deals. The import adds the ones Tina mentions in her videos.
