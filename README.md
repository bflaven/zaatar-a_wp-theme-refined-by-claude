# Spice up Zaatar WP theme with Claude Code

Refinement of the **Zaatar** WordPress theme (live on flaven.fr). The theme lives in `wordpress/wp-content/themes/zaatar/` — that is the only directory tracked in git and the only place code changes belong. WordPress core and plugins are a vanilla install and must not be modified.


# wp_flaven_refine_theme

Refinement project for the **Zaatar** WordPress theme powering [flaven.fr](https://flaven.fr).
Goal: normalize the code, remove obsolete files, and improve maintainability, UX, SEO and GEO friendliness — while keeping the existing design.

## Project layout



```bash
.
├── docker-compose.yml          # Local staging stack
├── wordpress/                  # Full WP install (mounted into the container)
│   └── wp-content/themes/zaatar/   # ← the theme being refined (only tracked dir)
├── _db_flaven_fr_*/            # Prod DB dumps (ignored by git)
├── _archives/                  # Old theme copies (ignored by git)
└── _prompt/                    # Working notes (ignored by git)
```

Only `wordpress/wp-content/themes/zaatar/` is tracked in git; WordPress core, plugins and uploads are ignored.

## Staging (Docker)

```bash
docker compose up -d      # start
docker compose down       # stop
```

| Service    | URL                    | Notes                          |
|------------|------------------------|--------------------------------|
| WordPress  | http://localhost:8080  | `WORDPRESS_DEBUG=1`            |
| phpMyAdmin | http://localhost:8081  | host `db`, root / root         |
| MySQL 8    | localhost:3307         | db `wordpress2`, root / root   |

## Importing a production dump

```bash
# 1. Import the SQL dump into the running db container
docker exec -i wp_docker-db-1 mysql -uroot -proot wordpress2 < _db_flaven_fr_160626/<dump>.sql

# 2. Rewrite prod URLs for local use (WP-CLI handles serialized data)
docker run --rm --network wp_flaven_refine_theme_wpnetwork \
  -v "$PWD/wordpress:/var/www/html" \
  -e WORDPRESS_DB_HOST=db:3306 -e WORDPRESS_DB_USER=root \
  -e WORDPRESS_DB_PASSWORD=root -e WORDPRESS_DB_NAME=wordpress2 \
  wordpress:cli wp search-replace 'https://flaven.fr' 'http://localhost:8080' --all-tables
```

## Project status

**Phase 1 done (2026-07-11)** — theme v1.2: dead code removed (7.0 MB → 4.0 MB),
text domain and headers normalized, versioned enqueues, accessibility fixes,
SEO/GEO meta module (`inc/seo.php`).

**Phase 2 done (2026-07-11)** — theme v1.3: functions.php split into inc/
modules, fitvids/enquire dropped for native CSS/JS, scripts deferred,
style.css dead rules removed, WCAG AA contrast + focus states, Font Awesome
subset to solid-woff2 (2.7 MB → 80 KB), Google Fonts display=swap.
See the theme changelog and `git log`.

**Phase 3 in progress** — interim releases delivered for manual FTP upload:
v1.3.1 (2026-07-12, steps 13-15: CSS custom properties, fluid typography,
native `<dialog>` mobile menu), v1.3.2 (2026-07-12, steps 16-17: jQuery
dropped, card layout for archives; confirmed in prod) and v1.3.3
(2026-07-12, step 18: sticky header). v1.4 lands when steps 19-20 are done.

**➡ The step-by-step program lives in [ROADMAP.md](ROADMAP.md).**

**Gotchas:**

- Pretty permalinks require the standard WordPress rewrite block in
  `wordpress/.htaccess` (it was empty; fixed manually — file is NOT tracked by git,
  so re-apply if the `wordpress/` folder is ever recreated).
- Autoptimize aggregates CSS/JS, so `?ver=` params don't appear in rendered HTML.
- Never run `docker compose down -v` — it deletes the `db_data` volume (the imported DB).

## AI meta descriptions — the monthly routine

Every post can carry two custom fields, rendered by the theme
(`inc/seo.php`) into the SEO/social tags:

| Custom field | Feeds | When |
|--------------|-------|------|
| `bf_ai_meta_description` | `<meta description>`, `og:description`, `twitter:description` | always used when set |
| `bf_ai_og_title` | `og:title`, `twitter:title` | set only when the real title > 60 chars |

Posts without the fields keep the excerpt/title fallback. Delete a field →
that post falls back again.

### 1. Generate (Mac, conda env `editorial_treatment` — stdlib only, no pip installs)

```bash
python3 tools/generate_meta_descriptions.py --limit 100 --source https://flaven.fr
```

Output: `tools/output/meta_descriptions.json` — **cumulative**. Every run keeps
all existing entries and only generates posts that are not in the file yet
(newest first). An interrupted run loses nothing; rerun to resume. A later run
for 10 new posts adds 10 entries, the older 100 stay.

All flags:

| Flag | Meaning | Default |
|------|---------|---------|
| `--limit N` | stop after N new posts this run | 0 = no limit |
| `--ids 12,34` | only these post IDs | all published posts |
| `--since 2026-08-01` | only posts published after that date | — |
| `--regenerate 12,34` | drop these IDs from the JSON and redo them | — |
| `--source URL` | WordPress to read posts from | `http://localhost:8080` (staging) |
| `--model NAME` | Azure deployment name | `mistral-small` |

Recipes:

```bash
# Monthly batch: next 100 newest posts not yet in the JSON
python3 tools/generate_meta_descriptions.py --limit 100 --source https://flaven.fr

# The post(s) you just published
python3 tools/generate_meta_descriptions.py --ids 13250,13251 --source https://flaven.fr

# Everything published after a date ("since last time")
python3 tools/generate_meta_descriptions.py --since 2026-08-01 --source https://flaven.fr

# Cap a since-run at 20 posts
python3 tools/generate_meta_descriptions.py --since 2026-08-01 --limit 20 --source https://flaven.fr

# Redo entries you don't like (the only flag that overwrites)
python3 tools/generate_meta_descriptions.py --regenerate 12815,12219 --source https://flaven.fr

# All remaining posts, no cap (hours; Ctrl-C safe, rerun resumes)
python3 tools/generate_meta_descriptions.py --source https://flaven.fr

# Staging as source (omit --source; needs docker compose up -d)
python3 tools/generate_meta_descriptions.py --limit 100

# Another Azure deployment
python3 tools/generate_meta_descriptions.py --limit 10 --model gpt-4.1-mini --source https://flaven.fr
```

**Choosing the source — staging (default) vs prod:**

Both work identically; pick by **freshness, not speed**.

- `http://localhost:8080` (the default, no `--source` needed) reads the local
  staging DB — a snapshot of the last prod dump. Posts published on flaven.fr
  after that dump **do not exist there**. Fine for backfilling old posts;
  needs `docker compose up -d`.
- `--source https://flaven.fr` always has every post, content up to date
  (edits included). Use it for recent/new posts and for the monthly batch.
- Speed is a non-argument: fetching happens once at startup (seconds — and
  with `--ids` it is a single request either way). Total runtime is dominated
  by the Azure rate limit at ~1 minute per post, identical for both sources.

Needs `.env` at the repo root with `ENDPOINT` (Azure AI Foundry) and `API_KEY`.
Prod notes baked into the script: flaven.fr only serves the REST API via
`?rest_route=` (handled automatically) and the POC Azure deployment rate-limits
hard (the script waits and retries; ~1 post/minute is normal, so 100 posts
take 1.5-2 h — leave it running, each entry is saved as it completes).

### 2. Review

Open the JSON, read the new entries (`description_chars` > 160 = too long),
edit text directly in the file if needed.

### 3. Import (prod or staging wp-admin)

Tools → **AI Meta** → Import tab → browse the JSON → **Import**. Existing
fields (including manual edits) are never overwritten; the report says
imported / skipped / no-match. The **Bulk review** tab lists all posts with
counters and filters ("Missing description") for hand-tuning afterwards.

One-off post? Skip the pipeline: paste the text into the post's Custom Fields
panel (field name `bf_ai_meta_description`), or use the Bulk review tab.

## The Zaatar theme

Clean, minimalist publishing theme based on [Underscores](https://underscores.me/), inspired by
Allium (TemplateLens), Totomo (GretaThemes) and Supreme (Swift Ideas). Accent color: purple `#4F1993`.

See `wordpress/wp-content/themes/zaatar/readme.md` for theme details and changelog.

## Author

Bruno Flaven — https://flaven.fr/
License: GPL v2 or later.


## Savvy commands for claude code and operational environment 

1. Some commands to leverage a pipeline. See `tools`. Connect with a wp plugin.

```bash
# OG GENERATE_META_DESCRIPTIONS

# path
cd /Users/brunoflaven/Documents/01_work/_wp_flaven_refine_theme/

# EXAMPLE
python3 tools/generate_meta_descriptions.py --ids 10436,10404 --source https://flaven.fr

# DONE_1
python tools/generate_meta_descriptions.py --ids 10386,10367,10346,10342,10315,10303,10290,10259,10255,10233 --source https://flaven.fr


# DONE_2
python tools/generate_meta_descriptions.py --ids 10220,10212,10163,10134,10104,10054,10038,9999,9971,9950 --source https://flaven.fr

# TODO_3 
python tools/generate_meta_descriptions.py --ids 9894,9884,3909,3896,3894,3875,3866,9353,3852,3854,3856 --source https://flaven.fr

# TODO_4 
python tools/generate_meta_descriptions.py --ids 3844,3847,3849,3653,3651,3646,8558,3626,3613,3615,3604 --source https://flaven.fr

# DONE_3 
python tools/generate_meta_descriptions.py --ids 3593,3595,3575,8501,3552,3554 --source https://flaven.fr

# RUNNING
python tools/generate_meta_descriptions.py --ids 9894,9884,3909,3896,3894,3875,3866,9353,3852,3854,3856,3844,3847,3849,3653,3651,3646,8558,3626,3613,3615,3604,3593,3595,3575,8501,3552,3554,3556,3529,8441,8414,8401,3516,3511,3514,3496,3494,8369,3491,8319,3489,3487,8291,3481,3467,3454,8264,8202,8189 --source https://flaven.fr

# WORST TO BEST
# START BY THE END
- 306,250,229,297,107,211,41,37,35,33,32

# 1083,1074,1061,1052,115,1026,1019,1005,990,985,973,965,943,937,910,892,873,864,829,800,776,742,712,696,95,659,594,61,582,580,570,544,535,527,492,479,475,473,39,439,429,425,2934,392,384,10,375,367,339,318

# 1514,1513,486,405,1496,1493,1488,1484,391,1482,1483,1478,1477,1473,1472,1445,136,1402,1378,1367,1359,1337,1303,1299,1290,1284,369,1278,1272,333,1263,317,1250,1246,1234,1222,1195,1187,1176,1135,1119,186,1113,179,152,139,1101,112,59,9345

# 8965,1616,1615,1611,8951,1593,1592,1588,8949,8750,8933,8814,1586,1585,1579,1562,1561,1558,8926,8915,1557,1556,1552,8907,8896,1549,1548,1539,8881,8871,8861,8836,8832,1537,1536,1535,1530,8826,8779,8777,1529,1528,1524,1523,8775,8770,1521,1516,497,1515

# 1804,1786,1784,1776,1774,1775,9187,1763,1762,1756,1755,9169,1754,9151,1752,1753,1751,9139,1750,1749,1748,9125,1746,1747,1745,1721,3080,9109,1717,1718,1705,9084,9067,9042,1671,9019,1668,1669,1670,1647,9011,9006,1645,1646,1641,9003,1619,8986,8970,1617

# 3104,1982,1963,9346,3008,2970,2949,1959,1958,1951,2921,1934,1935,1915,2887,2866,2834,2789,1911,1896,2760,2723,2716,2706,1895,2668,2629,1894,2604,2578,2568,1893,9260,1888,1887,1863,9241,9220,9203,1862,1855,1852,1851,1850,1849,1845,1835,1834,1825,1808

# 2005,5131,5094,5010,4580,4214,2002,2003,2004,4225,4364,4220,4140,4056,3954,4352,4341,3801,4248,4234,9349,4512,4190,3843,3813,3786,9348,3545,3750,4366,3830,3741,3551,3884,3707,3695,9347,3655,3645,3637,3625,3608,3539,3553,3821,3465,3298,2000,1990,3140


# 3075,2130,2122,6842,6801,3077,2121,6725,9352,2117,2102,9351,6578,2098,2089,9350,6467,6361,2085,2078,6339,6236,6186,6177,6146,5872,2077,5959,2069,2068,5723,5700,5682,5618,2058,2044,5586,5522,5500,5470,5444,5390,5361,5264,5240,5222,2031,2032,2017,2012

# 7782,7818,7794,7834,7750,7733,7645,7620,7589,7562,7541,7428,7338,7268,7326,7318,7311,7306,7299,7291,7283,7275,7261,7253,7240,3061,2205,2197,3063,2196,7161,3065,2190,2181,6887,3067,2180,7109,7056,3069,2170,2160,3071,2159,2138,6989,6897,3073,2137,2131

# 3182,3184,3154,3148,3130,3124,3112,8131,8083,8102,3039,3041,2295,8059,8025,3043,3045,2274,3047,3049,2245,8007,7988,7967,3051,2241,3053,2242,3055,2243,3057,2244,3059,2239,2212,7947,7942,7939,7936,7933,7930,7927,7924,7920,7917,7914,7911,7907,7902,7765

# python tools/generate_meta_descriptions.py --ids 3556,3529,8441,8414,8401,3516,3511,3514,3496,3494,8369,3491,8319,3489,3487,8291,3481,3467,3454,8264,8202,8189,3450,3452,3421,3418,3420,3394,3371,3369,3346,8175,3341,3333,3335,3309,3305,3308,3302,3300,3283,3273,3237,3214,3216,3205,8145,3201,3203,3189  --source https://flaven.fr

# python tools/generate_meta_descriptions.py --ids 10436,10404,10386,10367,10346,,,10303,10255,10233,10220,10212,10163,10134,10104,10054,10038,9999,9894,9884,3909,3896,3894,3875,3866,9353,3852,3854,3856,3844,3847,3849,3653,3651,3646,8558,3626,3613,3615,3604,3593,3595,3575,3552,3554 --source https://flaven.fr




# DONE 
python tools/generate_meta_descriptions.py --ids 9894,9884,3909,3896,3894,3875,3866,9353,3852,3854,3856,3844,3847,3849,3653,3651,3646,8558,3626,3613,3615,3604,3593,3595,3575,8501,3552,3554,3556,3529,8441,8414,8401,3516,3511,3514,3496,3494,8369,3491,8319,3489,3487,8291,3481,3467,3454,8264,8202,8189 --source https://flaven.fr
```


2. The anaconda env if needed for the pipeline. See `tools`. Connect with a wp plugin.

```bash

# CONDA ENVIRONMENT
# Conda Environment
conda create --name editorial_treatment python=3.12
conda info --envs
source activate editorial_treatment
conda deactivate
```


3. Open at least two consoles with Claude Code and Docker to creata a staging.

```bash
# CLAUDE (CONSOLE_1)
cd /Users/brunoflaven/Documents/01_work/_wp_flaven_refine_theme
claude

# DOCKER (CONSOLE_1)
cd /Users/brunoflaven/Documents/01_work/_wp_flaven_refine_theme/
docker compose down
docker compose up -d
```
