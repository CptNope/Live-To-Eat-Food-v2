# Live to Eat Food for WordPress

The brand book and the example site, turned into a real WordPress site: a **block theme** (`live-to-eat-food/`) and a small **companion plugin** (`ltef-core/`). The theme is the look. The plugin holds the content and the forms, so switching themes later never loses a place, a Woo-pon or a submission.

Requires WordPress 6.6 or newer (tested on 6.8) and PHP 7.4 or newer.

## Install

1. Build the zips: `bash wordpress/build-zips.sh` (they land in `wordpress/dist/`, which isn't committed).
2. In WordPress: **Plugins → Add New → Upload Plugin** → `ltef-core.zip` → Activate.
3. **Appearance → Themes → Add New → Upload Theme** → `live-to-eat-food.zip` → Activate.
4. **Tools → Live to Eat Food setup**, then press the three buttons in order:
   - **Create the pages:** Home, Guides, Restaurant Roulette, Food help, Send something in, Work with Tina, For restaurants, How it works, Editorial policy, Monthly Crumbs, Tina, Daily deals, Beyond food and Your bites. It also sets Home as the front page, Guides as the posts page and permalinks to `/%postname%/`.
   - **Import Tina's places and videos:** every place from her TikToks back to May 2024 and her Instagram posts that never went to TikTok (305 as of November 2026: food, plus the shops, wellness spots and things to do she has featured), with her caption as her line, plus 282 restaurants she hasn't been to yet across Worcester County, MetroWest and the Lowell area (address, phone and website from current public listings; hours left for someone to check), the 5 guides, 48 round-ups of her event, trip and older Instagram posts (back to 2017), the weekly deals she mentions in her videos (Daily deals), her partner codes for Woo-pons (Lensmart and Jeremy Anderson Tech; added once, so edits and deletions stick), and her other videos from each place. It skips anything already on the site (but a "not yet" place she has since been to gets her video and line, and an existing round-up gets any of her videos it's missing), and any business whose name matches the private don't-work-with list, so press it again after each update to bring in what's new.
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

- **What kind of place:** Food & drink (the default), Shop, Wellness & beauty, Services, Things to do or Community. Food & drink places go on Explore, Restaurant Roulette and Tina's latest picks. Everything else goes on **Beyond food** (`/beyond-food/`), grouped by kind with a sticker for each (bag, flower, camera, ticket, heart), and its label reads "Tina went here". Explore shows 24 at a time with a Show more button (the filters search the whole list; without JavaScript every place shows). To move a place between the two, change its kind.
- **More videos from here:** her other videos from the same place, one per line as `month | link | her caption`. They show small under "More from Tina here", newest first, each with her caption.
- **Tina's video (any link)** is a block for posts: a link (or several copies, separated by spaces), the month and her caption. The round-ups under *Out & about* use it.
- Captions are Tina's own words from the post, with emoji and @ signs taken out. Where a caption had a promo code or a date that has passed, only the first sentence is used.

### Where Tina's videos play from

Each place has Tina's video link (where she first posted it) and three optional copies: **YouTube**, **Instagram** and **Facebook**. The site plays the first one it has, in this order:

1. **YouTube**, in its privacy-enhanced player: blocked least by schools, offices and privacy tools.
2. **Instagram**
3. **Facebook** (the video has to be Public, and the link has to be the video's own: `facebook.com/reel/…` or `…/videos/…`; share links only get linked to)
4. **TikTok**: blocked most often.

None of them needs the visitor to have an account for a public video (checked signed out in November 2026).

The import fills in the YouTube copy from Tina's channel (@LiveToEatFoodie) for 273 places and 172 of her other videos, matched by place name and posting date (November 2026). It only fills empty fields, so a link typed in by hand stays. TikTok's player turns some networks away (HTTP 429, "Too many requests"), which is why YouTube goes first. For a new video, paste its Shorts link into the place's **YouTube copy** field. Under the player, "Not playing? Watch it on …" links to every copy. Nothing loads from any platform until someone presses play. To change the order, filter `ltef_video_priority`.

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

## The admin

The admin wears the site's colors. The plugin adds a **Live to Eat Food** color scheme (basil menu, lemon for where you are, the site's fonts) and moves everyone on WordPress’s default scheme (Fresh, or Modern on newer WordPress, where it’s labeled Default) to it once; anyone can pick another under **Users → Profile → Admin Color Scheme**, and that choice sticks. The plugin's own screens (Review queue, Settings, Email, Payments, Money, Live to Eat Food setup) are laid out as white sticker panels and work on a phone: the review queue is a card per submission with Approve, Edit first and a folded Decline. The sign-in page is the lemon sheet with the wordmark. The fonts and wordmark come from the theme's folder.

The scheme is built from WordPress's own scheme template: edit `ltef-core/assets/css/src/admin-colors.scss`, then run `ltef-core/assets/css/build-admin.sh` (needs Dart Sass and a WordPress install at `WP_CORE`, default `/tmp/wp`).

## Owners update their own listing

Any place can be claimed from its page (“Is this your business? Claim this listing”) or from **For restaurants → Claim your listing**. The claim can also suggest the listing’s phone, website, menu link, hours and note. It lands in the review queue with the place it names, the listing’s phone number and which details it would change: Tina calls that number, then approves, and the suggested details go live (logged, so she can undo them). Approving adds the owner’s email to the place and emails them how to sign in.

- **Their login:** approving a claim makes a WordPress account with the **Business owner** role and emails a link to set a password (if the email already has an account, it keeps its role; one without editing rights also becomes a Business owner). Signing in lands on **Your listing**, the only screen the role has besides Profile: no dashboard, posts, pages, media, comments, other places or settings, no front-end admin bar and no application passwords. Which places a login can edit follows the owner emails on each place, so removing an email in the Owners box removes that access; if an owner changes their email on Profile, their listings follow it.
- **Or an email link:** For restaurants → Update your listing also offers a one-time link that works for two hours, for owners who’d rather not use a password. The page always answers the same way, so it can’t be used to find out who owns what. Links are rate-limited per email and per connection, and the page they open is never cached, indexed or sent as a referrer.
- **What owners can change:** phone, website, menu link, hours (saving marks them checked today) and a note of up to 280 characters shown as “From the owner.” Never Tina’s line, verdict, videos or labels.
- **Changes go live right away.** Tina gets an email listing each change, and the place’s **Owners** box (on its edit screen) keeps a log with **Undo** for the newest change to each field. The same box adds or removes owner emails (adding one can make their login and email them), for example when someone claims a place by phone.

## Enhanced listings ($29 a month)

An approved owner can upgrade from **Your listing**. The price is **Enhanced listing** under Money → Prices & split; a new price applies to new upgrades only.

- **Paying:** Stripe Checkout starts a monthly subscription (card or Link). Stripe’s webhook switches Enhanced on, records each month’s payment and Stripe’s fee in Money (income source “Enhanced listing”), and switches it off when the subscription ends or goes unpaid. The site’s webhook listens for `invoice.paid` and `customer.subscription.updated/deleted` as well; a webhook connected before this was added is updated automatically the next time an admin opens the dashboard.
- **Changing the card:** **Update card** on Your listing opens Stripe’s customer portal for that listing’s subscription (change the card, see past invoices; cancelling stays on the site). The portal settings are made by the site through Stripe’s API the first time, once per mode. When a payment fails, the button is highlighted and Stripe retries with the new card.
- **Cancelling:** from Your listing. It stays on until the end of the month already paid for (“Keep Enhanced” undoes it until then). Photos and text are kept in case they come back. Tina gets an email for each upgrade, cancel and end.
- **What it adds:** up to 8 photos (each with a description for screen readers), the owner’s story (700 characters), up to 10 menu highlights with prices and up to 3 booking or ordering links, in a “From [place]” section after Tina’s take and the facts, labeled Enhanced listing. In Explore the row shows their first photo, and the start of their story when Tina hasn’t been. Changes go live at once and are logged; an added photo can be undone from the Owners box.
- **By hand:** ticking Enhanced listing on a place still works (for a deal arranged directly); the owner then sees “arranged with Tina” instead of the plan.

## Tina's own business

Tick **Tina’s own business** on a place she owns (Tinaesthetics by Dr. Vo is the first). It's in the import data too. The place then shows a hatched "Tina’s own business" label everywhere it appears, its page says so in words and links the Editorial policy, and it never shows a verdict. FTC advertising rules ask creators to disclose a business they own; the same goes in her videos about it (said and on screen), which the site can't do for her.

## Accessibility, privacy and terms

The footer links to **Accessibility**, **Privacy policy** and **Terms of use** (built-in pages, so **Create the pages** adds them; it fills WordPress's own draft Privacy Policy page instead of making a second one, and sets it as the site's privacy page). They describe what the site actually does as of November 11, 2026: Tina runs Live to Eat Food and is responsible for the policies, and Jeremy Anderson Tech LLC builds and manages the site for her; visit counts from Independent Analytics (no cookies, daily-rotating visitor hash, kept two years) and no ad trackers; data goes to Cloudways, Cloudflare (Turnstile), Brevo, Stripe and Rackspace; forms are for 13 and up; Massachusetts law. Independent Analytics (from the WordPress plugin directory) is set to refresh its visitor salt daily, delete data after two years, and not track logged-in users; the Privacy policy says so, so keep those settings. If any of that changes (a new service, a new form, an analytics setting), update the pages and their date.

On November 11, 2026, axe-core (WCAG 2.2 A and AA rules) found no failures on 30 page types at 1280px and 390px, including the open menus and the sign-in page. Keyboard focus, 320px reflow and reduced motion were checked too. The one fix was a visible focus ring on date fields, whose focus sits inside the field.

## Still to do before launch

- **The Monthly Crumbs welcome email.** Set it up as an automation in Brevo on the Monthly Crumbs list.
- **Real content.** Delete the `[Sample]` posts, add the real ads as they're sold, and check every place's hours.
- **A privacy page and a terms page.** The forms collect emails, phone numbers and photos.

## Checked

On WordPress 6.8.3 (SQLite), from the zips, on a fresh install: every page returns 200 with no PHP warnings; every page and pattern opens in the editor with no invalid blocks; axe (WCAG 2.2 AA) reports no violations at 390 and 1280 px wide; no page scrolls sideways; the forms submit into the review queue, and Approve publishes.

## Daily deals

Weekly specials live under **Places → Daily deals**: pick the place, tick the days, write the deal in a few words, and say where it comes from (Tina's post, the place, or a reader checked with the place). The Daily deals page lists them Monday to Sunday with today marked, and each place's "Before you go" box shows its weekly deals. The import adds the ones Tina mentions in her videos and the ones places post themselves, and pressing it again brings deals it added up to date with the data. A deal someone has edited by hand under Places → Daily deals is left alone.

Last fact-checked November 9, 2026 against Tina's videos and each place's own website, Facebook or Instagram: Antonio's Monday deals (online ordering menu), Volturno's Monday/Tuesday BOGO and The Fix's Wagyu Hot Dog Thursdays (their Facebook), 'Olo's Tuesday wings and slices (their Facebook, June), Oshare's all-you-can-eat prices (their Facebook) and VIA's $19.99 entrées (their website). Antonio's Tuesday Pasta Madness, La Scala's Wednesday chicken night and Chashu's Wednesday three courses rest on Tina's videos (June to October 2026).

## Partner codes and referral links

The online section of Woo-pons shows Tina's partner codes (Woo-pons with How it runs set to *Partner code*) and her referral shops (Amazon storefront, LCI Paper). Each says how she earns: pick *A commission* or *A referral fee* on the code. *Where to use it* and *Shop button text* are optional. Every partner link opens in a new tab with `rel="sponsored"`, and none of them appear on Roulette, guides or her picks.

## Spam check (Cloudflare Turnstile)

Every form on the site already has a hidden trap field, a three-second minimum and a limit of 12 sends an hour from one connection. Cloudflare Turnstile adds a check that a person is sending, usually without asking them anything (it asks for a click only when something looks off).

1. In Cloudflare, open **Turnstile → Add widget**: hostname livetoeatfoodie.com, mode Managed.
2. Paste the **site key** and **secret key** under **Review queue → Settings → Spam check** and save.
3. When "Check it works" shows a tick, press **Save settings** again. Cloudflare confirms the keys belong together, and only then do the forms start being checked, so a wrong key can't break them.
4. Optional: tick **Check the sign-in and lost-password pages too**. It also waits for that confirmation, so it can't lock you out.

If the server can't reach Cloudflare, the forms let the message through rather than lose it, and the last error is kept in the `ltef_turnstile_last_error` option.

## Email (Brevo)

**Review queue → Email** connects the site to Brevo with an API key (Brevo: SMTP & API → API keys). Once it's saved:

- **Monthly Crumbs list:** every signup goes straight onto the list you pick. Signups are still saved on the site too, so if Brevo is ever down nothing is lost: the page shows how many haven't reached Brevo and a button sends them.
- **The site's own emails:** with *Send them through Brevo* ticked, new-submission notifications and WordPress's emails (password resets and the rest) go out through Brevo from the address set there (tina@livetoeatfoodie.com). If Brevo refuses one, WordPress's own mail is tried and the reason shows on the page.
- **Send test** emails any address (tina@livetoeatfoodie.com by default), which checks Brevo sending and the Rackspace inbox in one go.

livetoeatfoodie.com is authenticated in Brevo (DKIM, DMARC and the branded `em.` subdomain are in Cloudflare DNS). Mailboxes stay on Rackspace.

## Payments (Stripe)

**Review queue → Payments** connects Stripe with one secret key (Stripe: Developers → API keys). Saving it also creates the webhook in Stripe that reports payments back to the site (`/wp-json/ltef/v1/stripe`, signed and checked). Start with the test key (`sk_test_…`) and Stripe's test cards, then paste the live key.

- **Paid forms** (weekly and VIP Woo-pons, premium events, job posts of 1–3 months): after the form, Stripe's checkout page takes a card and **holds** the amount. Card numbers never touch the site. The review queue shows "Card held". **Approve** charges it and puts it up; **Decline** releases the hold. The free Woo-pon trial needs no card.
- **No card, or the hold ran out** (Stripe holds last 7 days): Approve emails a one-time Stripe pay link instead, and the listing goes up when it's paid.
- **Custom deals** (ad spots, Monthly Crumbs ads, sponsorships, paid visits): *Send a pay link* on the Payments page with who, what and the amount.
- **Money**: every payment is logged as income (method Stripe) with Stripe's fee as an expense. Refunds made in Stripe come in as negative income.
- Prices come from Money → Settings. With no key saved or the switch off, the paid forms work as before (payment collected by hand).
