import argparse
import json
import re
import time
from pathlib import Path
from typing import Any, Dict, List, Optional
from urllib.parse import urljoin, urlparse

import requests
from bs4 import BeautifulSoup

BASE_URL = "https://iloader.site/premium-ipas/"
DEFAULT_OUTPUT_JSON = "premium_ipas.json"
DEFAULT_ASSETS_DIR = "assets"


def slugify(value: str) -> str:
    value = value.strip().lower()
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-") or "item"


def safe_ext_from_url(url: str, default: str = ".jpg") -> str:
    path = urlparse(url).path
    ext = Path(path).suffix.lower()
    if ext and re.match(r"^\.[a-z0-9]{1,5}$", ext):
        return ext
    return default


def clean_text(node: Optional[Any]) -> str:
    if not node:
        return ""
    return node.get_text(" ", strip=True)


def parse_meta_line(meta_line: str) -> Dict[str, str]:
    parts = [p.strip() for p in meta_line.split("•")]
    parts = [p for p in parts if p]
    out = {"developer_name": "", "category": "", "version_date": ""}
    if len(parts) >= 1:
        out["developer_name"] = parts[0]
    if len(parts) >= 2:
        out["category"] = parts[1]
    if len(parts) >= 3:
        out["version_date"] = parts[2]
    return out


def find_ld_json(soup: BeautifulSoup) -> Dict[str, Any]:
    script = soup.find("script", {"type": "application/ld+json"})
    if not script:
        return {}
    raw = script.string or script.get_text(strip=True) or ""
    if not raw:
        return {}
    try:
        return json.loads(raw)
    except json.JSONDecodeError:
        return {}


def get_info_value(soup: BeautifulSoup, key_name: str) -> str:
    for row in soup.select(".detail-list > div"):
        label = clean_text(row.find("strong"))
        if label.lower() == key_name.lower():
            return clean_text(row.find("span"))
    return ""


def download_file(session: requests.Session, url: str, dest: Path) -> bool:
    try:
        with session.get(url, timeout=30, stream=True) as resp:
            if resp.status_code != 200:
                return False
            dest.parent.mkdir(parents=True, exist_ok=True)
            with dest.open("wb") as f:
                for chunk in resp.iter_content(chunk_size=8192):
                    if chunk:
                        f.write(chunk)
        return True
    except requests.RequestException:
        return False


def parse_listing_cards(soup: BeautifulSoup) -> List[Dict[str, Any]]:
    cards: List[Dict[str, Any]] = []
    for card in soup.select("article.store-card"):
        title = clean_text(card.select_one("h3"))
        subtitle = clean_text(card.select_one(".store-card-copy p"))
        badge_version = clean_text(card.select_one(".badge")).lstrip("v").strip()
        meta_line = clean_text(card.select_one(".store-card-copy small"))
        description = clean_text(card.select_one(".store-description"))
        meta = parse_meta_line(meta_line)

        icon_emoji = clean_text(card.select_one(".icon-emoji"))
        icon_initials = clean_text(card.select_one(".icon-initials"))
        icon = f"{icon_emoji} {icon_initials}".strip()

        icon_img = card.select_one("img.app-icon")
        icon_url = icon_img.get("src", "").strip() if icon_img else ""
        if icon_url:
            icon_url = urljoin(BASE_URL, icon_url)

        detail_link_node = card.select_one("a.download-button")
        detail_url = ""
        if detail_link_node and detail_link_node.get("href"):
            detail_url = urljoin(BASE_URL, detail_link_node["href"])

        cards.append(
            {
                "name": title,
                "icon": icon,
                "developer_name": meta["developer_name"],
                "subtitle": subtitle,
                "category": meta["category"] or card.get("data-category", "").strip(),
                "version": badge_version,
                "version_date": meta["version_date"],
                "description": description,
                "iconURL": icon_url,
                "detailURL": detail_url,
            }
        )
    return cards


def enrich_from_detail(session: requests.Session, entry: Dict[str, Any]) -> None:
    detail_url = entry.get("detailURL", "")
    if not detail_url:
        entry["downloadURL"] = ""
        entry["screenshots"] = []
        entry["price"] = None
        entry["tool_type"] = "unknown"
        return

    try:
        resp = session.get(detail_url, timeout=30)
        resp.raise_for_status()
    except requests.RequestException:
        entry["downloadURL"] = ""
        entry["screenshots"] = []
        entry["price"] = None
        entry["tool_type"] = "unknown"
        return

    soup = BeautifulSoup(resp.text, "html.parser")
    ld_json = find_ld_json(soup)

    if not entry.get("description"):
        entry["description"] = clean_text(soup.select_one(".detail-card p"))
    else:
        full_description = clean_text(soup.select_one(".detail-card p"))
        if full_description:
            entry["description"] = full_description

    if not entry.get("version"):
        entry["version"] = get_info_value(soup, "Version")
    if not entry.get("category"):
        entry["category"] = get_info_value(soup, "Category")
    if not entry.get("subtitle"):
        entry["subtitle"] = clean_text(soup.select_one(".app-detail-head .subtle"))

    if not entry.get("developer_name"):
        author = ld_json.get("author", {})
        if isinstance(author, dict):
            entry["developer_name"] = str(author.get("name", "")).strip()

    if not entry.get("iconURL"):
        icon_url = ""
        if isinstance(ld_json.get("image"), str):
            icon_url = ld_json.get("image", "").strip()
        if not icon_url:
            icon_url = (
                soup.select_one("img.app-detail-icon").get("src", "").strip()
                if soup.select_one("img.app-detail-icon")
                else ""
            )
        entry["iconURL"] = urljoin(BASE_URL, icon_url) if icon_url else ""

    download_url = ld_json.get("downloadUrl", "")
    entry["downloadURL"] = str(download_url).strip() if isinstance(download_url, str) else ""

    offers = ld_json.get("offers", {})
    price = None
    if isinstance(offers, dict):
        raw_price = offers.get("price")
        if raw_price is not None:
            try:
                price = float(raw_price)
            except (TypeError, ValueError):
                price = None
    entry["price"] = price
    if price is None:
        entry["tool_type"] = "unknown"
    else:
        entry["tool_type"] = "free" if price <= 0 else "paid"

    screenshots: List[str] = []
    for img in soup.select("img"):
        src = (img.get("src") or "").strip()
        if not src:
            continue
        full_src = urljoin(detail_url, src)
        if "iloader.svg" in full_src.lower():
            continue
        if entry.get("iconURL") and full_src == entry["iconURL"]:
            continue
        screenshots.append(full_src)
    # Keep insertion order while removing duplicates.
    seen = set()
    unique_screenshots = []
    for shot in screenshots:
        if shot in seen:
            continue
        seen.add(shot)
        unique_screenshots.append(shot)
    entry["screenshots"] = unique_screenshots


def save_assets(session: requests.Session, entries: List[Dict[str, Any]], assets_dir: Path) -> None:
    icons_dir = assets_dir / "icons"
    shots_dir = assets_dir / "screenshots"
    icons_dir.mkdir(parents=True, exist_ok=True)
    shots_dir.mkdir(parents=True, exist_ok=True)

    for idx, entry in enumerate(entries, start=1):
        slug = slugify(entry.get("name", f"app-{idx}"))
        icon_url = entry.get("iconURL", "")
        if icon_url:
            ext = safe_ext_from_url(icon_url, ".jpg")
            icon_path = icons_dir / f"{idx:04d}-{slug}{ext}"
            if download_file(session, icon_url, icon_path):
                entry["icon_asset"] = str(icon_path.as_posix())
            else:
                entry["icon_asset"] = ""
        else:
            entry["icon_asset"] = ""

        screenshot_assets: List[str] = []
        for shot_idx, shot_url in enumerate(entry.get("screenshots", []), start=1):
            ext = safe_ext_from_url(shot_url, ".jpg")
            shot_path = shots_dir / f"{idx:04d}-{slug}-{shot_idx:02d}{ext}"
            if download_file(session, shot_url, shot_path):
                screenshot_assets.append(str(shot_path.as_posix()))
        entry["screenshot_assets"] = screenshot_assets


def run(output_json: Path, assets_dir: Path, limit: int, delay: float) -> None:
    session = requests.Session()
    session.headers.update(
        {
            "User-Agent": (
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/126.0.0.0 Safari/537.36"
            )
        }
    )

    listing_resp = session.get(BASE_URL, timeout=30)
    listing_resp.raise_for_status()
    listing_soup = BeautifulSoup(listing_resp.text, "html.parser")
    entries = parse_listing_cards(listing_soup)

    if limit > 0:
        entries = entries[:limit]

    for i, entry in enumerate(entries, start=1):
        enrich_from_detail(session, entry)
        if delay > 0:
            time.sleep(delay)
        if i % 25 == 0:
            print(f"Processed {i}/{len(entries)} tools...")

    save_assets(session, entries, assets_dir)

    output_json.parent.mkdir(parents=True, exist_ok=True)
    output_json.write_text(json.dumps(entries, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"Saved {len(entries)} records to: {output_json}")
    print(f"Assets saved under: {assets_dir}")


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Scrape iLoader Premium IPAs metadata, assets, and JSON export."
    )
    parser.add_argument(
        "--output",
        default=DEFAULT_OUTPUT_JSON,
        help=f"Output JSON file path (default: {DEFAULT_OUTPUT_JSON})",
    )
    parser.add_argument(
        "--assets-dir",
        default=DEFAULT_ASSETS_DIR,
        help=f"Directory to save image assets (default: {DEFAULT_ASSETS_DIR})",
    )
    parser.add_argument(
        "--limit",
        type=int,
        default=0,
        help="Limit number of tools to scrape (0 = all).",
    )
    parser.add_argument(
        "--delay",
        type=float,
        default=0.05,
        help="Delay between detail page requests in seconds (default: 0.05).",
    )

    args = parser.parse_args()
    output_json = Path(args.output).resolve()
    assets_dir = Path(args.assets_dir).resolve()
    run(output_json=output_json, assets_dir=assets_dir, limit=args.limit, delay=args.delay)


if __name__ == "__main__":
    main()
