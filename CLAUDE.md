# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this project is

Refinement of the **Zaatar** WordPress theme (live on flaven.fr). The theme lives in
`wordpress/wp-content/themes/zaatar/` — that is the only directory tracked in git and the
only place code changes belong. WordPress core and plugins are a vanilla install and must
not be modified.

## Hard rules

- **Never read or modify directories starting with underscore** (`_archives/`, `_prompt/`, `_db_*/`).
- Do not edit WordPress core (`wp-admin/`, `wp-includes/`, root `wp-*.php`) or plugins.
- Keep the existing visual design; no redesign without an explicit request.
- Text domain is `zaatar` everywhere. Function prefix is `allium_` (historical, kept for
  back-compat with customizer settings — do not mass-rename without checking `theme_mod` keys).
- The site content is real production data; treat the local DB as disposable staging.

## Project status

Phases 1 and 2 (steps 0-12) done, user-validated (2026-07-11), theme at v1.3.

**Resume protocol: read `ROADMAP.md` first.** The first ⬜ todo step there is
the current task. Phase 3 (UX modernization, steps 13-20, target v1.4) is
planned but GATED: user uploads v1.3 to prod manually and confirms it works
before phase 3 starts. Work one step at a time: implement → lint → smoke test
→ commit → tick the step's status in ROADMAP.md. Get user validation at each
checkpoint.

## Staging

- `docker compose up -d` → WordPress at http://localhost:8080, phpMyAdmin at :8081.
- DB: MySQL 8 in container `wp_docker-db-1`, database `wordpress2`, root/root, prefix `wp_`.
- DB persists in the `db_data` volume; never `docker compose down -v` (wipes it).
- Pretty permalinks need the standard WP rewrite block in `wordpress/.htaccess`
  (untracked by git; was found empty once and fixed manually — re-apply if missing).
- Autoptimize aggregates CSS/JS: `?ver=` params won't show in rendered HTML.
- WP-CLI: not installed in the wordpress container; run the `wordpress:cli` image on the
  `wp_flaven_refine_theme_wpnetwork` network (see README.md for the exact command).
- Smoke test after changes: `curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/`
  plus a visual check of home, a single post, an archive, search and 404.

## Verification

- Lint PHP before committing: `php -l <file>` for every touched PHP file
  (or via docker: `docker exec wp_docker-wordpress-1 php -l /var/www/html/wp-content/themes/zaatar/<file>`).
- `WORDPRESS_DEBUG=1` is on: watch the rendered pages for notices/warnings.

## Theme facts

- Based on Underscores; mixes Allium / Totomo / Supreme lineage. Accent purple `#4F1993`.
- Custom post types come from **plugins** (`bf_quotes_manager`, `bf_videos_manager`,
  `he3_product_to_sale`, clients…); the theme only provides their templates
  (`archive-*.php`, `taxonomy-*.php`, `template-parts/content/content-*.php`).
- SEO: the JSON-LD Settings plugin (`json_ld/json_ld_settings.php`) outputs Article +
  BreadcrumbList structured data; Google Site Kit and Jetpack are active. The theme's
  `inc/seo.php` adds meta description, Open Graph and Twitter Cards only — it must never
  emit JSON-LD, and it self-disables when Yoast/RankMath/AIOSEO/SEOPress is active.
  It disables Jetpack's duplicate OG/Twitter tags (keep those filters), and on singular
  posts it prefers the custom fields `bf_ai_meta_description` / `bf_ai_og_title`
  (AI meta descriptions pipeline: `tools/generate_meta_descriptions.py` +
  `bf-ai-meta-import` plugin — see README "AI meta descriptions").
- Two companion plugins are git-tracked (exceptions in .gitignore) and must stay in
  sync with the theme: `bf-ai-meta-import` (meta importer/bulk editor) and
  `bf-amazon-widget` (legacy widget extracted from the theme — old API on purpose).
- Classic editor + widgets; jQuery-based JS (superfish menu + hoverIntent, custom.js).
- Removed in v1.2 (do not resurrect): functions_0/2/3/4.php, search_0.php,
  `inc/page-builder/` (Swift page builder, was never loaded).
- Removed in v1.3 (do not resurrect): js/fitvids.js and js/enquire.js (native
  CSS aspect-ratio / window.matchMedia instead), the blanket async script
  filter and ?ver stripper, FontAwesome brands/regular faces and non-woff2
  formats (only fa-solid-900.woff2 ships), `.stuck` sticky-nav CSS.
- functions.php is now thin; admin columns live in `inc/admin-columns.php`.
  The legacy Amazon widget moved to the `bf-amazon-widget` companion plugin
  (git-tracked, old widget API + historical widget ID kept on purpose);
  `inc/widgets.php` is gone — do not resurrect it in the theme.
