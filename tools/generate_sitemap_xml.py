#!/usr/bin/env python3
"""Regenerate sitemap.xml for flaven.fr from the staging database.

Usage:  python3 tools/generate_sitemap_xml.py > wordpress/sitemap.xml
Then upload wordpress/sitemap.xml to the site root (robots.txt points at
/sitemap.xml).

Replicates the historical prod sitemap format: single-line XML, per-URL
<lastmod> (post_modified_gmt; for taxonomy terms the newest assigned
post), priorities 0.90 (home, posts, pages), 0.70 (category/ and tag/
roots), 0.60 (everything else). URL bases for the plugin CPTs/taxonomies
mirror prod (quotes, videos, clients, books, authors-quotes,
flavors-quotes, videos-categories, videos-tags, book-author,
book-description). product_for_sale_genre terms and clients-category are
deliberately absent, as they were in the historical sitemap (their URL
bases are unverifiable from staging where the books plugin is inactive).
"""

import subprocess
import sys
from xml.sax.saxutils import escape

SITE = "https://flaven.fr"

CPT_BASES = {
    "bf_quotes_manager": "quotes",
    "bf_videos_manager": "videos",
    "clients": "clients",
    "product_for_sale": "books",
}

TAX_BASES = {
    "category": "category",
    "post_tag": "tag",
    "bf_quotes_manager_author": "authors-quotes",
    "bf_quotes_manager_flavor": "flavors-quotes",
    "bf_videos_manager_cat": "videos-categories",
    "bf_videos_manager_tag": "videos-tags",
    "product_for_sale_author": "book-author",
    "product_for_sale_kw": "book-description",
}

# archive/landing roots present in the historical sitemap (base, priority)
ROOTS = [
    ("category", "0.70"), ("tag", "0.70"),
    ("quotes", "0.60"), ("videos", "0.60"), ("books", "0.60"),
    ("clients", "0.60"),
    ("authors-quotes", "0.60"), ("flavors-quotes", "0.60"),
    ("videos-categories", "0.60"), ("videos-tags", "0.60"),
    ("book-author", "0.60"), ("book-type", "0.60"),
]


def sql(query):
    raw = subprocess.run(
        ["docker", "exec", "wp_docker-db-1", "mysql", "-uroot", "-proot",
         "wordpress2", "--batch", "-N", "-e", query],
        capture_output=True, check=True,
    ).stdout.decode("utf-8", errors="replace")
    return [line.split("\t") for line in raw.splitlines() if line]


def w3c(ts):
    return ts.replace(" ", "T") + "+00:00"


def page_paths():
    """id -> full hierarchical path for published pages."""
    rows = sql("SELECT ID, post_parent, post_name FROM wp_posts "
               "WHERE post_type='page' AND post_status='publish';")
    by_id = {int(i): (int(par), slug) for i, par, slug in rows}
    def path(pid):
        par, slug = by_id[pid]
        return (path(par) + "/" if par in by_id else "") + slug
    return {pid: path(pid) for pid in by_id}


def main():
    urls = []  # (loc, lastmod, priority)

    newest = sql("SELECT MAX(post_modified_gmt) FROM wp_posts "
                 "WHERE post_status='publish';")[0][0]
    urls.append((f"{SITE}/", w3c(newest), "0.90"))

    # posts
    for slug, yyyymm, mod in sql(
        "SELECT post_name, DATE_FORMAT(post_date,'%Y/%m'), post_modified_gmt "
        "FROM wp_posts WHERE post_type='post' AND post_status='publish' "
        "ORDER BY post_date DESC;"
    ):
        urls.append((f"{SITE}/{yyyymm}/{slug}/", w3c(mod), "0.90"))

    # pages (hierarchical paths)
    paths = page_paths()
    for pid, mod in sql("SELECT ID, post_modified_gmt FROM wp_posts "
                        "WHERE post_type='page' AND post_status='publish';"):
        urls.append((f"{SITE}/{paths[int(pid)]}/", w3c(mod), "0.90"))

    # CPT singles
    for ptype, base in CPT_BASES.items():
        for slug, mod in sql(
            f"SELECT post_name, post_modified_gmt FROM wp_posts "
            f"WHERE post_type='{ptype}' AND post_status='publish';"
        ):
            urls.append((f"{SITE}/{base}/{slug}/", w3c(mod), "0.60"))

    # archive / landing roots
    for base, pri in ROOTS:
        urls.append((f"{SITE}/{base}/", w3c(newest), pri))

    # taxonomy terms (non-empty), lastmod = newest assigned published post;
    # category paths are hierarchical
    cat_parent = {}
    for tid, par, slug in sql(
        "SELECT tt.term_taxonomy_id, tt.parent, t.slug FROM wp_term_taxonomy tt "
        "JOIN wp_terms t ON t.term_id=tt.term_id WHERE tt.taxonomy='category';"
    ):
        cat_parent[int(tid)] = (int(par), slug)
    # parent field references term_id; remap term_id -> ttid slugs
    cat_by_termid = {}
    for tid, par, slug in sql(
        "SELECT t.term_id, tt.parent, t.slug FROM wp_term_taxonomy tt "
        "JOIN wp_terms t ON t.term_id=tt.term_id WHERE tt.taxonomy='category';"
    ):
        cat_by_termid[int(tid)] = (int(par), slug)
    def cat_path(term_id):
        par, slug = cat_by_termid[term_id]
        return (cat_path(par) + "/" if par in cat_by_termid else "") + slug

    for tax, base in TAX_BASES.items():
        for term_id, slug, mod in sql(
            "SELECT t.term_id, t.slug, MAX(p.post_modified_gmt) "
            "FROM wp_term_taxonomy tt "
            "JOIN wp_terms t ON t.term_id=tt.term_id "
            "JOIN wp_term_relationships tr ON tr.term_taxonomy_id=tt.term_taxonomy_id "
            "JOIN wp_posts p ON p.ID=tr.object_id AND p.post_status='publish' "
            f"WHERE tt.taxonomy='{tax}' AND tt.count>0 "
            "GROUP BY t.term_id, t.slug;"
        ):
            path = cat_path(int(term_id)) if tax == "category" else slug
            urls.append((f"{SITE}/{base}/{path}/", w3c(mod), "0.60"))

    parts = ["<?xml version='1.0' encoding='utf-8'?>"
             '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">']
    seen = set()
    for loc, mod, pri in urls:
        if loc in seen:  # e.g. a page slug that matches an archive root
            continue
        seen.add(loc)
        parts.append(f"<url><loc>{escape(loc)}</loc><lastmod>{mod}</lastmod>"
                     f"<priority>{pri}</priority></url>")
    parts.append("</urlset>")
    sys.stdout.write("".join(parts))
    print(f"generated: {len(seen)} urls ({len(urls) - len(seen)} duplicates dropped)",
          file=sys.stderr)


if __name__ == "__main__":
    main()
