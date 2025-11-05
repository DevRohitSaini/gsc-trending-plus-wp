## GSC Trending Plus

A small WordPress plugin that displays your most visited and trending posts using Google Search Console data. This repository contains the plugin source used to integrate with GSC and a lightweight frontend style.

Key features
- Show trending posts based on Search Console impressions and clicks
- Shortcode: [gsc_trending_posts]
- Admin settings page for configuring the Search Console connection
- Uses service account key authentication for server-to-server access

Requirements
- WordPress 6.8.3 or later
- PHP 7.4+ recommended

Installation

1. Copy the repository files into `/wp-content/plugins/gsc-trending-plus/` (or upload the ZIP via Plugins → Add New → Upload Plugin).
2. Activate the plugin in the WordPress admin.
3. Go to Settings → GSC Trending to configure your Google Service Account credentials and site property.
4. Place the shortcode `[gsc_trending_posts]` in a post, page, or widget to render the trending posts list.

Files in this repository
- `wp-gsc-trending.php` — main plugin file / entry point
- `assets/style.css` — basic stylesheet used by the frontend output

Usage

- Shortcode: `[gsc_trending_posts]`
	- Optional attributes may be supported in the plugin (check the settings or source for available options).
- The plugin retrieves Search Console data server-side and renders a list of posts with metrics like clicks, impressions, CTR, and average position.

Development notes

- This repository is intentionally small. If you add features, please keep changes documented in the changelog below.
- When testing locally, ensure you do not commit private service account keys to the repository. Use environment variables or a private config outside the repo.


Support / Contact

For issues, please open an issue in the repository. For quick questions, contact the author: rohit-saini (see the repo profile).


