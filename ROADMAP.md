# Zaatar refinement roadmap

Single source of truth for the step-by-step program. Each step ends with a
validation checkpoint (user checks the site) and one git commit. Update the
status column here as steps complete — this file is what tells the next
session where we are.

## Release map — which release ships which steps

Delivery is always the same: user uploads the theme folder by manual FTP,
checks the version in the footer on prod, purges the Autoptimize cache.

| Release | Date | Steps shipped | Content (one line) | Prod status |
|---------|------|--------------|--------------------|-------------|
| v1.2 | 2026-07-11 | 0-6 (phases 1-2 part) | Baseline, git, dead-code purge, text domain, a11y basics, seo.php meta/OG/Twitter | ✅ validated |
| v1.3 | 2026-07-11 | 7-12 | functions.php split, JS modernized (no fitvids/enquire), CSS cleanup, WCAG AA pass, webfonts 2.7 MB → 80 KB | ✅ validated 2026-07-12 |
| v1.3.1 | 2026-07-12 | 13-15 | CSS custom properties, fluid clamp() typography, native `<dialog>` mobile menu | ✅ validated |
| v1.3.2 | 2026-07-12 | 16-17 | jQuery dropped entirely, card grid layout for all archives | ✅ validated |
| v1.3.3 | 2026-07-12 | 18 | Sticky header (IntersectionObserver, no scroll listener) | ✅ validated |
| v1.4 | 2026-07-13 | 19-20 | Dark mode (data-theme + toggle, AA palette), micro-polish (anchor offset, smooth scroll, link underlines) | superseded by 1.4.1 |
| v1.4.1 | 2026-07-13 | 19-20 fixes | Toggle docked in social icon bar, #cv-wrap resume + semaphore widgets follow the toggle | ✅ validated 2026-07-13 |
| v1.4.2 | 2026-07-13 | 21 | SEO/GEO interim: og:image fallback card, og:image:alt, archive canonicals | ✅ validated (via 1.4.3) |
| v1.4.3 | 2026-07-13 | 22 | Semantic markup: visible updated date, one h1 per page (h2 cards, SR-only archive h1) | ✅ validated 2026-07-13 |
| v1.4.4 | 2026-07-13 | 24 | Self-hosted fonts (variable woff2, no Google origins), LCP/CLS pass | ✅ validated 2026-07-13 |
| v1.5 | 2026-07-13 | 25 (phase 5 complete) | Reading time in single meta, categories on the 404 | superseded by 1.6 (never uploaded) |
| v1.6 | 2026-07-14 | 26 (phase 4 start) | Translation refresh: fr_FR/es_ES 100%, fresh POT, submenu aria-label localized | ⬜ to upload |
| v1.7 | 2026-07-14 | 27, 28-32, 33 | Editor-style parity, AI meta description custom fields in seo.php + Jetpack og/twitter off, Amazon widget → bf-amazon-widget plugin | ⬜ to upload (theme + 2 companion plugins) |
| v1.8+ | ideas | phase 4 backlog | og:image backfill, TL;DR box, alt-text | unscheduled |

## Phase 1 — Baseline & cleanup ✅ DONE (2026-07-11, theme v1.2)

| Step | What | Status |
|------|------|--------|
| 0 | Import prod DB dump into docker staging, URL search-replace, fix .htaccess rewrite block | ✅ done |
| 1 | git init (theme-only tracking), .gitignore, README.md, CLAUDE.md, theme readme | ✅ done |
| 2 | Remove dead code: functions_0/2/3/4.php, search_0.php, inc/page-builder (7.0→4.0 MB) | ✅ done |
| 3 | Normalize: text domain `zaatar`, style.css header, dedupe theme supports, versioned enqueues | ✅ done |
| 4 | UX/a11y: skip-link to top of body, aria-labels on nav + toggle | ✅ done |
| 5 | SEO/GEO: inc/seo.php — meta description, Open Graph, Twitter Cards (no JSON-LD, plugin covers it) | ✅ done |
| 6 | QA: lint all PHP, smoke test all template types, docs + changelog 1.2 | ✅ done |

## Phase 2 — Refactor & modernize ⏳ NEXT (target: theme v1.3)

| Step | What | Validation | Status |
|------|------|------------|--------|
| 7 | **Refactor functions.php** (~1300 lines): split admin-columns block into `inc/admin-columns.php`, Amazon/legacy widgets into `inc/widgets.php`, review `delete_post_type()` hack (renamed `bf_unregister_feedback_post_type`); no behavior change | Admin list screens + widgets identical; lint + smoke | ✅ done (2026-07-11, pending user check of wp-admin list screens) |
| 8 | **JS modernization**: dropped fitvids.js (CSS aspect-ratio) and enquire.js (native matchMedia in custom.js); theme scripts defer via core strategy; removed blanket async filter and ?ver stripper (cache busting restored); superfish + hoverIntent kept | Menus (desktop hover + mobile toggle) and embeds work | ✅ done (2026-07-11, pending user visual check) |
| 9 | **CSS/responsive polish** of style.css: removed dead rules (WPBakery `.wpb_*`, `.help-text`, `.stuck` sticky-nav, `#page-wrap-resume`, `.paging-navigation`) + 4 orphan header_line images; verified `img/iframe max-width:100%`; TOC completed with sections 18-21 | Visual check desktop + mobile, no layout regressions | ✅ done (2026-07-11, pending user visual check) |
| 10 | **A11y pass**: purple #4F1993 passes AA everywhere (11:1); darkened failing grays (#a6a6a6/#999/#ccc → #767676/#757575) on menus, captions, credits, breadcrumb; visible focus for inputs (border+ring) and buttons/links (:focus-visible outline); submenu :focus-within fallback; aria-label on mobile dropdown-toggle; search + comment forms already labeled | Keyboard-only walkthrough; contrast checker | ✅ done (2026-07-11, pending user keyboard walkthrough) |
| 11 | **Performance**: Google Fonts `display=swap`; FontAwesome cut to solid woff2 only + `font-display: swap` (webfonts 2.7 MB → 80 KB; no fab/far usage in theme, posts or widgets); images already via core functions → auto srcset/lazy-load | Lighthouse/PageSpeed on staging before vs after | ✅ done (2026-07-11, pending user Lighthouse check) |
| 12 | **Release 1.3**: version bumped (style.css + readme stable tag), changelog 1.3 written, README.md/CLAUDE.md refreshed, full QA: lint all PHP clean, smoke home/single/page/category/tag/search/404/quotes/videos/clients archives+singles all 200 (product_for_sale 404s are pre-existing: registering plugin absent from staging). GitHub remote: not added (user's call) | User validates; clean git tree | ✅ done (2026-07-11) |

## Phase 3 — UX/webdesign modernization (target: theme v1.4)

**Gate: v1.3 must be uploaded to prod (manual FTP) and confirmed working first.**
Gate passed 2026-07-12 (flaven.fr footer showed Zaatar v1.3). Order below is the
coherent sequence, each step = one commit + user validation, same protocol as phase 2.

**Interim release 1.3.1 (2026-07-12): steps 13-15 + user's #cv-wrap CV-page CSS,
delivered mid-phase for manual FTP upload (theme folder as-is, no zip).**

**Interim release 1.3.2 (2026-07-12): steps 16-17 (jQuery dropped, card layout
for archives), same manual FTP delivery. Confirmed working in production
2026-07-12 (stale superfish.js/hover-intent.js also deleted from the server).**

**Interim release 1.3.3 (2026-07-12): step 18 (sticky header), same manual FTP
delivery. v1.4 still lands when steps 19-20 are done.**

**Release 1.4 (2026-07-13): steps 19-20 done — version bumped, changelog
written, all PHP lint clean, smoke home/single/page/archives/search/404 all
200, zero debug notices. Same delivery as always: user uploads the theme
folder by FTP and checks the version in the footer on prod.**

**Release 1.4.1 (2026-07-13): three post-1.4 dark-mode fixes after user
review — toggle docked in the header_social_icons bar, #cv-wrap resume
follows the toggle, semaphore widgets follow the toggle in both directions.
Full QA re-run (lint, smoke, footer shows v1.4.1). This is the version to
upload.**

| Step | What | Validation | Status |
|------|------|------------|--------|
| 13 | **CSS custom properties**: `:root` block (new section 0 in style.css TOC) — 11 color vars (accent, accent-2, text, text-dark, muted ×2, on-dark, white, bg-dark, border ×2) + 4-step spacing scale (15/26/30/32px as rem); 150 color + 148 spacing literals swapped to `var()`; px fallback lines kept; one-off colors left literal. Note for step 19: `--color-text-muted` (#757575) and `--color-text-muted-2` (#767676) are merge candidates | No visual change (regression check) | ✅ done (2026-07-12, pending user regression check) |
| 14 | **Fluid typography**: 10 `clamp()` vars in `:root` ramping 400→1024px viewport (body 16-18, h1-h6, blockquote 21-24, single title 26-38, page title 34-38); `--space-flow` fluid rhythm margin (26-32px) on p/h1-h6/blockquote/address/hr; `--line-height-body` 1.6→1.8 via :root media override; redundant 768px jumps deleted. ul/ol/dd/table/pre kept their 768px overrides (side margins change too, not pure rhythm) | Visual check desktop + mobile | ✅ done (2026-07-12, validated in prod via 1.3.2) |
| 15 | **Modern mobile menu**: native `<dialog>` server-rendered in site-navigation.php (second wp_nav_menu, filter `allium_header_menu_responsive_args`), no clone hack. All 4 audited issues fixed: toggle is a real `<button aria-expanded aria-controls>`, submenu toggles are `<button>` siblings after the `<a>` (not nested), focus trap/restore + Esc native to dialog, slide/backdrop animation wrapped in `prefers-reduced-motion: no-preference`. No-JS fallback: `<noscript>` in wp_head shows the regular menu, hides toggle. overlay-effect div + CSS removed (native `::backdrop`). New i18n string "Close menu" (.po refresh is phase 4) | Mobile menu keyboard + screen-reader pass | ✅ done (2026-07-12, pending user mobile keyboard/SR pass) |
| 16 | **Drop jQuery**: superfish.js + hover-intent.js deleted (desktop dropdowns already pure CSS :hover/:focus-within; arrow classes `sf-arrows`/`sf-with-ul`/`sfHover` swapped for WP's `menu-item-has-children`/`page_item_has_children` in style.css + rtl.css); custom.js, keyboard-image-navigation.js, customizer.js rewritten vanilla (table wrap, scroll-to-top via `window.scrollTo smooth`, arrow keys via `e.key`); no `jquery` dep left in enqueues. Note: staging menu is flat, so dropdown/arrow CSS is dormant — verified by parity, not visually | Menus + embeds + scroll-to-top work, no jquery enqueued by theme | ✅ done (2026-07-12, validated in prod via 1.3.2) |
| 17 | **Card layout for archives**: `.post-wrapper-archive` is now a CSS grid (`auto-fill, minmax(320px, 1fr)` — 1 col mobile, 2 with sidebar, 3 full-width); each hentry is a white card (border, 4px radius, soft shadow) with hover/focus-within elevation (translateY + shadow, wrapped in `prefers-reduced-motion: no-preference`); thumbnail edge-to-edge with `aspect-ratio: 4/3` (matches 700×525 `allium-featured` crop) + `object-fit: cover`; card body padding on `.entry-data-wrapper`. Old stacked list + 768/1024px side-by-side flex rules removed. CSS-only change — all archives share `content.php` markup (posts, search, quotes, videos, clients, taxonomies) | Visual check all archive types | ✅ done (2026-07-12, validated in prod via 1.3.2) |
| 18 | **Sticky header**: `.site-header` gets `position: sticky; top: 0; z-index: 300`; shadow only when pinned via `.is-pinned`, toggled by an IntersectionObserver zero-height sentinel in custom.js (no scroll listener, no lib); admin-bar offsets in section 16.0 (46px thick bar, 32px from 783px, 0 under 600px where the core bar is absolute and scrolls away). Whole header sticks (branding + menu, ~170px desktop) — if user finds it tall, a compact pinned state is the follow-up | Scroll check desktop + mobile | ✅ done (2026-07-12, pending user scroll check) |
| 19 | **Dark mode**: inline head script stamps `data-theme="dark\|light"` on `<html>` (localStorage `zaatar-theme`, else OS preference — no FOUC, follows OS changes while unset); header toggle button (moon/sun FA icons, `aria-pressed`); new scheme-aware vars `--color-surface/--color-bg-page/--color-bg-subtle/--color-text-soft` swapped in for white/gray literals (light mode pixel-identical); single `html[data-theme="dark"]` override block (section 22.0) — palette all AA (text 10.9:1, links #b18ae8 6.2:1, muted 6.7:1); `color-scheme: dark` for native controls; no-JS = light + hidden toggle; toggle docked in the header_social_icons bar via JS (plugin untouched, nav fallback); #cv-wrap resume follows the toggle too (dedicated dark overrides, user request 2026-07-13) | Both schemes, contrast re-check | ✅ done (2026-07-13, pending user check both schemes) |
| 20 | **Micro-polish**: `html { scroll-behavior: smooth }` behind `prefers-reduced-motion: no-preference`; scroll-to-top JS respects reduced motion too; `[id] { scroll-margin-top: calc(var(--header-height) + 1rem) }` with `--header-height` measured by ResizeObserver in custom.js (sticky header no longer covers skip-link/anchor targets); running-text links underlined (`.entry-content/.comment-content/.entry-summary`, WCAG 1.4.1 — more-link/buttons/image links excluded via `:has(> img)`); focus rings unified on `--color-link` (visible in both schemes) | Quick pass | ✅ done (2026-07-13, pending user quick pass) |

## Phase 5 — SEO/GEO + sharing ✅ DONE (2026-07-13, theme v1.5)

**Gate passed 2026-07-13: v1.4.1 uploaded to prod and validated by user.**
Plugin exploration done 2026-07-13 (read-only): related posts, visible
breadcrumbs and all JSON-LD are plugin territory (semaphore, breadcrumb-
migration, json_ld — no schema duplication, verified). GEO stack already
strong: webmcp manifest + REST tools, rag-semantic-search. Theme work below
stays clear of all that.

| Step | What | Validation | Status |
|------|------|------------|--------|
| 21 | **Harden inc/seo.php**: og:image/twitter:image fallback via new `images/default-og.png` (1200×630 branded gradient, generated — replace file to change the card, keep the name; covers the 215 thumbnail-less posts + home/archives), og:image:alt (thumbnail alt, else site name), canonical on non-singular views (`zaatar_canonical`, pagination-aware, skips search/404/date; core keeps singular; verified exactly one canonical per page). Side-fix: `bf_my_post_thumbnail_html` pointed to never-shipped default-thumbnail.png → now default-og.png. Note: /publications/ is a WP page, so core (unpaginated) canonical applies there — acceptable. og:locale + article times already existed | Facebook Sharing Debugger + X Card Validator on 2-3 URLs (incl. one post without thumbnail) | ✅ done (2026-07-13, pending user card-validator check) |
| 22 | **Semantic/GEO markup pass**: visible "Updated on <date>" in single-post meta when the modified day differs from publish day (same-day edits stay hidden microformat; new i18n string, .po refresh phase 4); heading hierarchy fixed — archive/home/search card titles h1→h2 via `is_singular()` in content.php (home had 21 h1s), 4 CPT archives (quotes/videos/clients/books) had their visible title commented out by design → added screen-reader-only `<h1 class="page-title screen-reader-text">` so every page has a heading root; `<time datetime>` microformats verified (published + updated already emitted); byline already `vcard`/`rel=author` — untouched | View-source + W3C validator | ✅ done (2026-07-13, pending user view-source check) |
| 23 | **llms.txt / sitemap.xml / robots.txt refresh** (revised after user shared prod snapshots `wordpress/*_prod.*`, git-ignored — llms.txt+sitemap were stale at 2025-10-16, zero 2026 URLs). Delivered as reusable generators in `tools/` (tracked): `generate_llms_txt.py` → `wordpress/llms.txt` (686 entries, 661 posts incl. 2026, new "AI agent access" section referencing webmcp manifest + feeds + sitemap, content collections, cp1252-mojibake fixed) and `generate_sitemap_xml.py` → `wordpress/sitemap.xml` (4180 URLs, prod-format parity: single-line, per-URL lastmod, priorities 0.90/0.70/0.60, CPT+taxonomy bases mirrored, dupes dropped; product_for_sale_genre + clients-category deliberately absent as in prod). Plus `wordpress/robots.txt` patch: `Allow:` uploads + theme images/css/js/webfonts under the `*` group (Googlebot rendering + FB/X og:image scrapers were blocked). Rerun both scripts after future publishing, re-upload. .htaccess: no change needed | User FTPs the 3 files to site root; `curl` llms.txt/sitemap.xml show 2026 posts; card validators fetch images | ✅ done (2026-07-13, pending user upload + curl check) |
| 24 | **Core Web Vitals**: Google Fonts self-hosted — Nunito Sans + Roboto served as variable woff2 (weight range 400-700, latin+latin-ext, 8 files ~265 KB in /webfonts, css/fonts-local.css with font-display swap; fonts.googleapis.com/gstatic requests gone, allium_fonts_url() kept but uncalled, editor-style switched too). LCP: core already stamps fetchpriority=high on the first archive card image + lazy on the rest (verified); singles show no featured image by design (allium_post_thumbnail_single mod off) — eager+fetchpriority added there anyway for if it's ever enabled. CLS: all theme imgs carry width/height (verified home/archive/single); placeholder img got dims; semaphore related-card imgs lack dims but plugin CSS fixes their height (no shift, plugin untouched) | Lighthouse before/after staging + PageSpeed prod | ✅ done (2026-07-13, pending user PageSpeed check) |
| 25 | **UX content extras** (revised: related posts REMOVED — semaphore already renders them on singles): `allium_reading_time()` in single-post meta (200 wpm, min 1, posts only, clock icon, `allium_reading_time_html` filter, new i18n string "%d min read"); 404 already had search/recent/archives/tags widgets — added Categories (with counts) | Visual check single + 404 | ✅ done (2026-07-13) |

## Phase 4 — Polish backlog (started 2026-07-14, target v1.6)

| Step | What | Validation | Status |
|------|------|------------|--------|
| 26 | **Translation refresh**: fresh `languages/zaatar.pot` via `wp i18n make-pot` (102 strings; replaces the stale 2020 POT and the duplicate `zaatar.po`, deleted); fr_FR + es_ES .po fully translated (100% — were 4/19 strings) incl. new strings "Close menu", "Updated on", "%d min read", "Toggle dark mode"; .mo compiled with `msgfmt --check` (0 errors); side-fix: hardcoded `'Toggle submenu'` aria-label in custom.js now localized via `wp_localize_script` (`alliumL10n`). Verified live by switching staging WPLANG to fr_FR then es_ES (core packs installed on staging): all theme strings render translated on single + home; reverted to en_US | Optional: user re-checks in Poedit; site is en-US so front-end unchanged | ✅ done (2026-07-14) |

| 27 | **Editor-style parity**: css/editor-style.css rewritten to mirror the current front-end reading experience (was the 2020 look: teal #04bfbf links, fixed px sizes, AA-failing #999/#ccc grays). Now: same `:root` custom-properties subset (light values only — wp-admin doesn't follow site dark mode), fluid clamp() type scale + --space-flow rhythm, purple --color-link + underlined links (image links excluded via :has), AA-darkened caption/cite grays, desktop (≥1200px front) values for tables/captions/aligned images since the editor canvas ≈ article column; dead ::selection/mark rules dropped (gone from front-end too); body selector modernized (`body.mce-content-body` + legacy `.mceContentBody`). fonts-local.css already loaded alongside via add_editor_style | User opens classic editor on staging: fonts, purple links, sizes match front | ✅ done, user-validated 2026-07-14 |

| 28 | **AI meta descriptions — spec** (user-validated 2026-07-14): custom fields `bf_ai_meta_description` (all posts) + `bf_ai_og_title` (only when title > 60 chars); theme inc/seo.php prefers them for meta description/og:description/twitter:description and og:title/twitter:title, excerpt/title fallback otherwise; pipeline `tools/generate_meta_descriptions.py` (Azure AI Foundry endpoint from root .env — API_KEY/ENDPOINT, model mistral-small-2503, stdlib only) fetches posts via public WP REST → reviewable JSON in tools/output/; **prod has no DB access** → delivery via importer plugin `bf-ai-meta-import` (FTP + wp-admin): tab 1 Import (reads bundled JSON, writes fields only where missing, report), tab 2 Bulk review (paginated editable table of descriptions/titles, char counters, save). Manual path always open: paste into Custom Fields panel per post; pipeline never overwrites existing fields. Later idea (separate): featured-image backfill for the 215 posts on the generic og card | Spec read + OK'd by user | ✅ done (2026-07-14) |
| 29 | **AI meta descriptions — generator**: `tools/generate_meta_descriptions.py` (stdlib only, .env ENDPOINT/API_KEY, Azure route `/models/chat/completions`, deployment `mistral-small`). Skips posts already in the JSON; `--limit/--ids/--since/--regenerate/--source/--model` flags; strips staging debug warnings from REST responses; heavy 429 backoff (POC endpoint also rejects any prompt > ~400 words → 120 content words sent). Prompt v2 after first review: full 150-160 budget, no Explore/Discover/Learn openers. 10 posts generated | User validated batch 2 (2026-07-14) | ✅ done, user-validated 2026-07-14 |
| 30 | **AI meta descriptions — render + import**: inc/seo.php prefers `bf_ai_meta_description` (meta description + og:description + twitter:description) and `bf_ai_og_title` (og:title + twitter:title) on singular posts, excerpt/title fallback intact; plugin `bf-ai-meta-import` v1.1 (git-tracked exception in plugins/): Import tab with JSON browse-upload (saved to plugin folder when writable) or bundled-file mode, Bulk review tab styled on breadcrumb-migration (stats chips, search, missing filter + row highlight, top+bottom tablenav with page-jump, char counters, explicit save). 10 posts imported on staging by user; view-source verified: all 5 tags AI on 13217, excerpt fallback on older posts | User ran import + validated (2026-07-14) | ✅ done, user-validated 2026-07-14 |
| 31 | **AI meta descriptions — rollout** (revised 2026-07-14: user drives it batch-wise instead of one full run): first 100 posts (newest first) generated by Claude session; user imported the first batch on prod himself (theme + plugin FTP'd, verified live on the WebMCP post — Jetpack duplicate-tag fix 882b9cd came out of that check). Remaining ~500: user reruns `python3 tools/generate_meta_descriptions.py --limit 100` (staging source; or `--source https://flaven.fr` — rest_route fallback + browser UA, 3679e54) every so often, reviews the JSON, browse-uploads it in Tools → AI Meta → Import (existing fields never overwritten) | User spot-checks each batch on prod | ⏳ ongoing (user routine, 100/600 done) |
| 32 | **AI meta descriptions — release**: seo.php changes (custom-field preference + Jetpack og/twitter off) folded into Release 1.7 with steps 27 and 33; full QA (69 theme files + 2 plugins lint clean, all templates 200, footer v1.7) | Release QA protocol | ✅ done (2026-07-14) |

| 33 | **Widget → plugin**: legacy Amazon single-product widget extracted from theme `inc/widgets.php` (deleted, require removed from functions.php) into companion plugin `bf-amazon-widget` (git-tracked). Code verbatim but callbacks renamed `bf_amazon_widget_*` (collision-proof) and registration deferred to widgets_init 99 with a function_exists('widget_amazon_init') step-aside — either upload order is safe on prod, no fatal during the migration window. Widget ID `amazon_single_product_widget` + option `widget_amazon_single_product` unchanged → sidebar placement + settings survive. Verified on staging: widget registered via plugin, home 200 | User FTPs plugin, activates, THEN uploads theme; widget still in sidebar | ✅ done (2026-07-14, pending user prod check) |

| 34 | **og:image backfill — investigated, parked** (2026-07-14): of the 215 thumbnail-less posts, 180 contain no image at all (the branded default-og.png card is the correct designed fallback for them), 1 hotlinks external, only 34 embed images — 2012-era files still live on prod (200) but FTP'd back then, never registered as attachments (absent from the 1011-item media library, not on staging disk). Only possible fix = sideload plugin (download → create attachment → set featured) for those 34 old portfolio posts. Deemed not worth the effort for now; revisit if those posts matter | User decides later | ⏸ parked |

Still unscheduled (discuss first):

- More editorial AI: TL;DR box (visible key-takeaways block, GEO play), alt-text
  backfill (needs a vision deployment, e.g. gpt-4.1-mini on the Azure endpoint).
- Housekeeping: git remote (offsite backup), git committer identity
  (`git config --global user.name/email` — every commit warns).

## Session bootstrap (how to resume)

1. `docker compose up -d` → http://localhost:8080 (DB persists in `db_data` volume).
2. Read this file — first ⬜ todo step is the current task.
3. Work one step at a time: implement → lint (`php -l` via docker) → smoke test → commit → tick the step here.
