# === Zaatar ===

- Contributors: Bruno Flaven, TemplateLens, GretaThemes, Swift Ideas
- Tags: two-columns, left-sidebar, right-sidebar, custom-background, custom-colors, custom-header, custom-menu, editor-style, featured-images, flexible-header, rtl-language-support, sticky-post, theme-options, threaded-comments, translation-ready, blog, news
- Requires at least: WordPress 5.9
- Tested up to: WordPress 6.8
- Requires PHP: 7.4
- Stable tag: 1.7
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html

## == Description ==

Zaatar is a clean and minimalist theme that allows your reader to focus on your content.
Designed for news agencies, travel websites, business magazines, food recipes, health
magazines, technology sites and all types of publishing or review sites. Accent color:
purple `#4F1993`.

Based on [Underscores](https://underscores.me/), inspired by Allium (TemplateLens),
Totomo (GretaThemes) and Supreme (Swift Ideas).

Features:

* Mobile-first, responsive layout
* Custom colors, custom header, custom logo
* Two menus (Header Menu: three levels, Top Menu: one level)
* RTL language support, translation ready (fr_FR, es_ES included)
* Template support for custom post types provided by companion plugins
  (quotes, videos, clients, products for sale)
* SEO & GEO friendly: semantic HTML5 markup, Open Graph metadata, structured-data ready

## == Installation ==

1. Copy the `zaatar` folder into `wp-content/themes/`.
2. In your admin panel, go to Appearance → Themes and activate Zaatar.
3. Navigate to Appearance → Customize and customize to taste.

## == Copyright ==

Zaatar WordPress Theme, Copyright Bruno Flaven — https://flaven.fr/
Portions Copyright 2019 TemplateLens.com (Allium) and GretaThemes (Totomo).

Zaatar is distributed under the terms of the GNU GPL v2 or later.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

## == Bundled resources ==

### Fonts

* Font Awesome Free 5.6.3 — Dave Gandy, https://fontawesome.com
  License: Icons CC BY 4.0, Fonts SIL OFL 1.1, Code MIT
  (since 1.3 only the Solid face ships, as fa-solid-900.woff2)

### CSS

* Bootstrap v4.1.3 (custom build) — © 2011-2018 The Bootstrap Authors / Twitter, Inc.
  License: MIT — http://getbootstrap.com

### JS

* hoverIntent r7 — © 2007-2013 Brian Cherne — MIT
* jQuery Superfish v1.7.10 — © 2018 Joel Birch — MIT/GPL — https://github.com/joeldbirch/superfish

(enquire.js and FitVids were removed in 1.3 — replaced by native
window.matchMedia and CSS aspect-ratio.)

### Images

* screenshot.png images licensed CC0 — https://stocksnap.io/
  (CBAV1C95EO, 2XXA0XX9G0, PF3QF43DKH, B3M57Q3I7H)

## == Changelog ==

### = 1.7 =
Released: July 14, 2026

* AI meta descriptions: singular posts prefer the custom fields
  bf_ai_meta_description (meta description, og:description,
  twitter:description) and bf_ai_og_title (og:title, twitter:title),
  set by the companion bf-ai-meta-import plugin or by hand; excerpt
  and title fallbacks unchanged for posts without them.
* Jetpack's duplicate Open Graph / Twitter Card tags disabled — the
  theme's inc/seo.php is the single source of those tags.
* Editor-style parity: the classic-editor stylesheet mirrors the
  current front-end (custom properties, fluid type scale, purple
  underlined links, AA caption grays) instead of the 2020 look.
* Legacy Amazon single-product widget extracted to the
  bf-amazon-widget companion plugin (inc/widgets.php removed);
  widget ID and settings unchanged, sidebar placement survives.

### = 1.6 =
Released: July 14, 2026

* Translation refresh (phase 4 step 26): fr_FR and es_ES are now 100%
  translated (102 strings each, .mo recompiled), covering the strings
  added since 1.3.1 ("Close menu", "Toggle dark mode", "Updated on",
  "%d min read", "Categories"). Fresh languages/zaatar.pot generated
  with wp i18n make-pot; the stale untranslated zaatar.po was removed.
* The mobile submenu toggle aria-label ("Toggle submenu") injected by
  custom.js is now translatable (localized via wp_localize_script).

### = 1.5 =
Released: July 13, 2026

* Reading time: single posts show an estimated "min read" (200 words
  per minute) next to the date, with a clock icon.
* Richer 404: a Categories list (with post counts) joins the search
  form, recent posts, archives and tag cloud.
* Closes phase 5 (SEO/GEO + sharing): og:image fallback card and
  archive canonicals (1.4.2), semantic dates and one-h1 hierarchy
  (1.4.3), self-hosted fonts (1.4.4), refreshed llms.txt/sitemap.xml/
  robots.txt at the site root (delivered separately).

### = 1.4.4 =
Released: July 13, 2026

* Performance (phase 5 step 24): Google Fonts are now self-hosted
  (Nunito Sans + Roboto variable woff2, latin + latin-ext, in
  /webfonts with font-display: swap) - no more requests to
  fonts.googleapis.com / fonts.gstatic.com. Featured images on singles
  load eagerly at high priority when enabled; image dimensions
  verified everywhere (no layout shift).

### = 1.4.3 =
Released: July 13, 2026

* Semantic markup (phase 5 step 22): single posts display "Updated on"
  with the modified date when a post was edited on a later day; list
  pages (home, archives, search) now use h2 card titles so each page
  has exactly one h1; the quotes/videos/clients/books archives get an
  invisible (screen-reader) h1 since their visible title is off by
  design.

### = 1.4.2 =
Released: July 13, 2026

* Sharing/SEO (phase 5 step 21): posts without a featured image, the
  homepage and archives now share with a branded fallback card
  (images/default-og.png, 1200x630 - replace the file to change the
  card, keep the name); og:image:alt added; pagination-aware canonical
  URL on archive/home views (core keeps handling posts and pages).

### = 1.4.1 =
Released: July 13, 2026

* Dark-mode toggle docked at the far right of the header social icon
  bar (header_social_icons plugin bar; outlined monochrome tile so it
  reads as a control, falls back to the main navigation when the
  plugin is inactive).
* The resume block (#cv-wrap) now follows the dark-mode toggle.
* Semaphore widgets (semantic sidebar, related-posts grid, related
  tags) now follow the toggle in both directions instead of the OS
  preference only.

### = 1.4 =
Released: July 13, 2026

* Dark mode: the site follows the OS color scheme and a moon/sun toggle
  in the header lets visitors override it (choice stored in
  localStorage, applied before first paint - no flash). Full dark
  palette passes WCAG AA; buttons keep their purple background and
  white text in both schemes. Without JavaScript the site stays light.
* Anchor comfort: skip-link and in-page anchors now land below the
  sticky header (scroll-margin-top driven by the measured header
  height); smooth scrolling for anchors and the scroll-to-top button,
  both disabled for prefers-reduced-motion users.
* Links inside article and comment text are underlined (WCAG 1.4.1:
  color is no longer the only cue); read-more, buttons and image links
  stay clean. Focus rings are visible in both color schemes.

### = 1.3.3 =
Released: July 12, 2026

* Sticky header: the site header now sticks to the top of the viewport
  while scrolling, with a soft shadow once pinned (IntersectionObserver
  sentinel, no scroll listener, no library). Offsets handled for the
  WordPress admin bar at every breakpoint.

### = 1.3.2 =
Released: July 12, 2026

* jQuery dropped: superfish.js and hover-intent.js deleted (desktop
  dropdowns are pure CSS :hover/:focus-within, arrow styling now keys
  on core menu-item-has-children classes); custom.js,
  keyboard-image-navigation.js and customizer.js rewritten in vanilla
  JS (table wrap, smooth scroll-to-top, arrow-key navigation); the
  theme no longer enqueues jquery.
* Card layout for archives: archive lists are a responsive CSS grid
  (1 column mobile, 2 beside the sidebar, 3 full-width); each entry is
  a white card with border, radius and soft shadow, lifting on
  hover/focus (respects prefers-reduced-motion); thumbnails run
  edge-to-edge at aspect-ratio 4:3 with object-fit cover, removing
  layout shift. Applies to posts, search, quotes, videos, clients and
  taxonomy archives.

### = 1.3.1 =
Released: July 12, 2026

* CSS custom properties: :root block (section 0 of style.css) with 11
  color vars and a 4-step spacing scale; 150 color and 148 spacing
  literals now reference var(). No visual change.
* Fluid typography: clamp() type scale ramping between a 400px and
  1024px viewport (body 16-18px, headings, blockquote, post title
  26-38px, page title 34-38px), fluid block margin (--space-flow,
  26-32px) and reading line-height 1.6-1.8; the hard 768px typography
  jump is gone.
* Modern mobile menu: server-rendered native <dialog> replaces the
  jQuery clone hack. Real toggle <button> with aria-expanded, submenu
  toggles no longer nested inside links, native focus trap/restore and
  Escape, ::backdrop overlay, animations honor prefers-reduced-motion,
  <noscript> fallback shows the regular menu without JS.
* Scoped styles for the CV/resume page block (#cv-wrap).

### = 1.3 =
Released: July 11, 2026

* functions.php refactored (1530 -> ~570 lines): admin list-table columns
  moved to inc/admin-columns.php, legacy Amazon widget to inc/widgets.php.
  delete_post_type() renamed bf_unregister_feedback_post_type().
* JS modernized: fitvids.js dropped (CSS aspect-ratio sizes video embeds),
  enquire.js dropped (native window.matchMedia in custom.js). Theme
  scripts now load with the core defer strategy; removed the legacy
  blanket-async filter and the ?ver query-string stripper.
* style.css cleaned: dead rules removed (WPBakery leftovers, sticky-nav
  .stuck, resume page, .paging-navigation), orphan header_line images
  deleted, table of contents completed (sections 18-21).
* Accessibility: WCAG AA contrast for gray text on light backgrounds
  (#a6a6a6/#999/#ccc darkened), visible focus for form fields
  (purple border + ring) and buttons/links (:focus-visible outline),
  submenu :focus-within fallback, aria-label on the mobile
  submenu toggle.
* Performance: Google Fonts loaded with display=swap; Font Awesome
  reduced to the Solid face as woff2 with font-display: swap
  (webfonts 2.7 MB -> 80 KB).

### = 1.2 =
Released: July 11, 2026

* Removed obsolete files: old functions.php variants (functions_0/2/3/4.php,
  search_0.php), unused Swift page builder (inc/page-builder, 2.7 MB).
  Theme size: 7.0 MB -> 4.0 MB.
* Text domain normalized to `zaatar` (style.css header fixed); version,
  Requires at least / Tested up to / Requires PHP fields added.
* functions.php cleaned: dead SF_* constants removed, duplicate
  add_theme_support() calls consolidated into allium_setup().
* Modern theme supports added: responsive-embeds, HTML5 script/style/search-form.
* Asset enqueues cache-busted with new ZAATAR_VERSION constant.
* Accessibility: skip-link moved to top of <body>, aria-labels added on
  primary navigation and mobile menu toggle.
* New SEO/GEO module (inc/seo.php): meta description, Open Graph and
  Twitter Card tags with featured image and article timestamps. Emits no
  JSON-LD (provided by the JSON-LD Settings plugin) and self-disables when
  a dedicated SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress) is active.
* Documentation refreshed (readme, project README.md, CLAUDE.md).

### = 1.1 =

* AI features added (01/02/2026). Zaatar fork consolidated from Allium/Totomo/Supreme.

### = 1.0.3 =
Released: March 17, 2019

* Display Featured Image at Single Posts option added.
* Theme prefix added in PHP variables.
* Code updated. POT file updated.

### = 1.0.2 =
Released: March 3, 2019

* Top Menu added (one level). Text domain fixed. POT file updated.

### = 1.0.1 =
Released: February 10, 2019

* Read More Label option added.

### = 1.0 =
Released: February 7, 2019
