#!/usr/bin/env python3
"""Generate AI meta descriptions (and og titles) for WordPress posts.

Fetches published posts from the WordPress REST API, asks an LLM on the
Azure AI Foundry endpoint (root .env: ENDPOINT + API_KEY) for a meta
description per post — plus a short og title when the post title exceeds
60 characters — and writes a reviewable JSON file. Nothing is written to
WordPress: the bf-ai-meta-import plugin (or a manual paste into the
Custom Fields panel) does that, from the reviewed JSON.

Usage:
  python3 tools/generate_meta_descriptions.py                 # all posts
  python3 tools/generate_meta_descriptions.py --limit 10      # first batch
  python3 tools/generate_meta_descriptions.py --ids 123,456   # specific posts
  python3 tools/generate_meta_descriptions.py --since 2026-07-01
  python3 tools/generate_meta_descriptions.py --source https://flaven.fr

Existing entries in the output JSON are kept and their posts skipped, so
successive runs only process new posts. Delete an entry (or use
--regenerate ID) to redo one.
"""

import argparse
import html
import json
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUTPUT = Path(__file__).resolve().parent / "output" / "meta_descriptions.json"
API_VERSION = "2024-05-01-preview"
DESCRIPTION_TARGET = "150-160"
DESCRIPTION_MAX = 170
OG_TITLE_MAX = 60
TITLE_THRESHOLD = 60  # og title generated only above this length
CONTENT_WORDS = 120  # words of post body sent to the model — the POC
                     # Azure deployment 429s any request past ~400 words
USER_AGENT = (
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/126 Safari/537.36 zaatar-meta-pipeline"
)


def load_env(path):
    env = {}
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, value = line.partition("=")
        env[key.strip()] = value.strip().strip('"').strip("'")
    return env


def http_json(url, payload=None, headers=None, retries=6):
    data = json.dumps(payload).encode("utf-8") if payload is not None else None
    request = urllib.request.Request(url, data=data, method="POST" if data else "GET")
    request.add_header("User-Agent", USER_AGENT)
    for key, value in (headers or {}).items():
        request.add_header(key, value)
    if data:
        request.add_header("Content-Type", "application/json")
    last = None
    for attempt in range(retries):
        try:
            with urllib.request.urlopen(request, timeout=120) as response:
                raw = response.read().decode("utf-8")
                try:
                    return json.loads(raw), dict(response.headers)
                except json.JSONDecodeError as error:
                    # Staging runs WORDPRESS_DEBUG=1: PHP warnings may be
                    # printed before the JSON body — parse from the first
                    # bracket instead.
                    start = min(
                        (i for i in (raw.find("["), raw.find("{")) if i != -1),
                        default=-1,
                    )
                    if start > 0:
                        try:
                            return json.loads(raw[start:]), dict(response.headers)
                        except json.JSONDecodeError:
                            pass
                    context = raw[max(0, error.pos - 200): error.pos + 200]
                    raise SystemExit(
                        f"Invalid JSON from {url} at char {error.pos} "
                        f"(a PHP notice in the response?):\n...{context}..."
                    )
        except urllib.error.HTTPError as error:
            body = error.read().decode("utf-8", "replace")[:300]
            last = f"HTTP {error.code} on {url}: {body}"
            if error.code in (429, 500, 502, 503) and attempt < retries - 1:
                retry_after = error.headers.get("Retry-After")
                wait = int(retry_after) if retry_after and retry_after.isdigit() else 0
                # POC endpoint sends Retry-After: 1 while enforcing a
                # per-minute window — never wait less than the backoff floor
                wait = max(wait, 20 * (attempt + 1))
                print(f"    rate-limited (HTTP {error.code}), waiting {wait}s ...")
                time.sleep(wait)
                continue
            raise SystemExit(last)
        except urllib.error.URLError as error:
            last = f"Network error on {url}: {error.reason}"
            if attempt < retries - 1:
                time.sleep(2 * (attempt + 1))
                continue
            raise SystemExit(last)
    raise SystemExit(last)


def strip_html(text):
    text = re.sub(r"<(script|style)[^>]*>.*?</\1>", " ", text, flags=re.S | re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = html.unescape(text)
    return re.sub(r"\s+", " ", text).strip()


def rest_url(source, params):
    """REST endpoint URL; falls back to ?rest_route= when the site does
    not serve /wp-json/ (flaven.fr prod answers 404 on the pretty route)."""
    source = source.rstrip("/")
    if not hasattr(rest_url, "use_rest_route"):
        probe = urllib.request.Request(source + "/wp-json/wp/v2/posts?per_page=1&_fields=id")
        probe.add_header("User-Agent", USER_AGENT)
        try:
            with urllib.request.urlopen(probe, timeout=30):
                rest_url.use_rest_route = False
        except urllib.error.HTTPError as error:
            if error.code != 404:
                raise
            rest_url.use_rest_route = True
    if rest_url.use_rest_route:
        return source + "/?" + urllib.parse.urlencode({"rest_route": "/wp/v2/posts", **params})
    return source + "/wp-json/wp/v2/posts?" + urllib.parse.urlencode(params)


def fetch_posts(source, since=None, ids=None):
    posts = []
    page = 1
    while True:
        params = {
            "per_page": "100",
            "page": str(page),
            "status": "publish",
            "_fields": "id,link,title,content,date",
            "orderby": "date",
            "order": "desc",
        }
        if since:
            params["after"] = since + "T00:00:00"
        if ids:
            params["include"] = ",".join(str(i) for i in ids)
        url = rest_url(source, params)
        try:
            batch, headers = http_json(url)
        except SystemExit as error:
            # WP returns 400 rest_post_invalid_page_number past the last page
            if "HTTP 400" in str(error) and page > 1:
                break
            raise
        if not batch:
            break
        for post in batch:
            posts.append(
                {
                    "id": post["id"],
                    "url": post["link"],
                    "title": strip_html(post["title"]["rendered"]),
                    "text": " ".join(strip_html(post["content"]["rendered"]).split()[:CONTENT_WORDS]),
                    "date": post["date"],
                }
            )
        total_pages = int(headers.get("X-WP-TotalPages", "1"))
        if page >= total_pages:
            break
        page += 1
    return posts


def build_prompt(post, want_og_title):
    task = (
        f"SEO meta description for this post: {DESCRIPTION_TARGET} chars — use the full "
        "budget, close to 160 but never over. Write in the language of the title "
        "(French title = French description). Active voice, name the "
        "main topic, be specific (tools, outcomes), no quotes/emoji/clickbait. "
        "Never open with Explore/Discover/Learn/Uncover or similar imperative verbs; "
        "vary openings (subject, question, claim).\n"
    )
    if want_og_title:
        task += f"Also og_title: social-card title, max {OG_TITLE_MAX} chars, keeps main keyword.\n"
    task += (
        'JSON only: {"description": "..."'
        + (', "og_title": "..."' if want_og_title else "")
        + "}\n"
        f"Title: {post['title']}\n"
        f"Text: {post['text']}"
    )
    return task


def call_llm(endpoint, api_key, model, prompt):
    url = endpoint.rstrip("/") + f"/models/chat/completions?api-version={API_VERSION}"
    payload = {
        "model": model,
        "temperature": 0.3,
        "max_tokens": 150,
        "messages": [
            {"role": "system", "content": "You are an SEO editor. You answer with valid JSON only."},
            {"role": "user", "content": prompt},
        ],
    }
    body, _ = http_json(url, payload, {"api-key": api_key, "Authorization": f"Bearer {api_key}"})
    content = body["choices"][0]["message"]["content"].strip()
    content = re.sub(r"^```(?:json)?\s*|\s*```$", "", content)
    return json.loads(content)


def write_output(existing):
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    ordered = sorted(existing.values(), key=lambda entry: entry["id"], reverse=True)
    OUTPUT.write_text(json.dumps(ordered, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
    return ordered


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--source", default="http://localhost:8080", help="WordPress base URL")
    parser.add_argument("--limit", type=int, default=0, help="max posts to process this run")
    parser.add_argument("--ids", default="", help="comma-separated post IDs")
    parser.add_argument("--since", default="", help="only posts published after YYYY-MM-DD")
    parser.add_argument("--regenerate", default="", help="comma-separated post IDs to redo")
    parser.add_argument("--model", default="mistral-small", help="Azure deployment name (mistral-small serves mistral-small-2503)")
    args = parser.parse_args()

    env = load_env(ROOT / ".env")
    endpoint, api_key = env.get("ENDPOINT"), env.get("API_KEY")
    if not endpoint or not api_key:
        raise SystemExit("ENDPOINT / API_KEY missing from .env")

    existing = {}
    if OUTPUT.exists():
        existing = {entry["id"]: entry for entry in json.loads(OUTPUT.read_text(encoding="utf-8"))}
    for post_id in [int(i) for i in args.regenerate.split(",") if i.strip()]:
        existing.pop(post_id, None)

    ids = [int(i) for i in args.ids.split(",") if i.strip()]
    print(f"Fetching posts from {args.source} ...")
    posts = fetch_posts(args.source, args.since or None, ids or None)
    todo = [p for p in posts if p["id"] not in existing]
    if args.limit:
        todo = todo[: args.limit]
    print(f"{len(posts)} published posts, {len(existing)} already in JSON, {len(todo)} to generate.\n")

    for index, post in enumerate(todo, 1):
        want_og_title = len(post["title"]) > TITLE_THRESHOLD
        result = call_llm(endpoint, api_key, args.model, build_prompt(post, want_og_title))
        description = result["description"].strip()
        if len(description) > DESCRIPTION_MAX:
            retry = call_llm(
                endpoint, api_key, args.model,
                f"Shorten to under 160 characters, same language, JSON only "
                f'{{"description": "..."}}: {description}',
            )
            description = retry["description"].strip()
        og_title = (result.get("og_title") or "").strip() if want_og_title else ""
        entry = {
            "id": post["id"],
            "url": post["url"],
            "title": post["title"],
            "title_chars": len(post["title"]),
            "description": description,
            "description_chars": len(description),
            "og_title": og_title or None,
            "og_title_chars": len(og_title) if og_title else None,
            "model": args.model,
            "generated_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        }
        existing[post["id"]] = entry
        write_output(existing)  # incremental save: a killed run loses nothing
        print(f"[{index}/{len(todo)}] #{post['id']} {post['title'][:70]}", flush=True)
        print(f"    desc ({entry['description_chars']}): {description}", flush=True)
        if og_title:
            print(f"    og_title ({entry['og_title_chars']}): {og_title}", flush=True)
        time.sleep(3)  # POC endpoint rate limit is tight

    ordered = write_output(existing)
    print(f"\nWrote {len(ordered)} entries to {OUTPUT.relative_to(ROOT)}")
    flagged = [e for e in ordered if e["description_chars"] > 160]
    if flagged:
        print(f"Review length on {len(flagged)} entries over 160 chars: "
              + ", ".join(str(e["id"]) for e in flagged))


if __name__ == "__main__":
    main()
