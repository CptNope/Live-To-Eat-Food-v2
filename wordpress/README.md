# Live to Eat Food for WordPress

The brand book and the example site, turned into a real WordPress site: a **block theme** (`live-to-eat-food/`) and a small **companion plugin** (`ltef-core/`). The theme is the look. The plugin holds the content and the forms, so switching themes later never loses a place, a Woo-pon or a submission.

Requires WordPress 6.6 or newer (tested on 6.8) and PHP 7.4 or newer.

## Install

1. Build the zips: `bash wordpress/build-zips.sh` (they land in `wordpress/dist/`, which isn't committed).
2. In WordPress: **Plugins → Add New → Upload Plugin** → `ltef-core.zip` → Activate.
3. **Appearance → Themes → Add New → Upload Theme** → `live-to-eat-food.zip` → Activate.
4. **Tools → Live to Eat Food setup**, then press the three buttons in order:
   - **Create the pages:** Home, Guides, Restaurant Roulette, Food help, Send something in, Work with Tina, For restaurants, How it works, Editorial policy, Monthly Crumbs, Tina, Daily deals and Your bites. It also sets Home as the front page, Guides as the posts page and permalinks to `/%postname%/`.
   - **Import Tina's places:** the 52 places, with her quotes, dates and videos, and the 5 guides as posts.
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
