=== Easy CSS Sync ===
Contributors: MSG317
Tags: css, github, sync, webhook, theme override
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fetch custom CSS from GitHub with optional webhook auto-updates, bypassing theme’s "Additional CSS" field.

== Description ==

Easy CSS Sync lets you pull CSS overrides from a GitHub repository and enqueue them in WordPress. Optionally, set up a GitHub webhook for near-instant updates when you push new commits. By default, it also checks hourly via WP-Cron. Perfect for teams who want to manage style changes in version control rather than editing code directly in the WordPress admin.

Features include:
* Pull a single CSS file from a GitHub repo (public or private).
* Automatic updates via cron or manual refresh.
* Optional GitHub Webhook integration for instant syncing.
* Stores CSS in a WordPress option to avoid extra HTTP requests on every page load.

== Installation ==

1. Download the plugin and upload it to your `wp-content/plugins/` directory, or install directly through the WordPress Plugins screen.
2. Activate **Easy CSS Sync** through the “Plugins” menu in WordPress.
3. In the admin menu, go to “Easy CSS Sync” and configure:
   - GitHub Raw URL (e.g. `https://raw.githubusercontent.com/username/repo/branch/style.css`)
   - Optional GitHub token for private repos or rate-limit concerns
   - (Optional) Webhook secret for secure auto-updates from GitHub

== Frequently Asked Questions ==

= Why isn’t my CSS updating? =
Ensure the GitHub Raw URL is valid, or that your token is correct for private repos. If you rely on WP-Cron, make sure your host hasn’t disabled it. For webhooks, confirm the payload URL and secret in GitHub matches the plugin settings.

= Where is the CSS stored? =
Easy CSS Sync saves your latest fetched CSS to a WordPress option named `easy_css_sync_options`. This means no extra network calls on the frontend.

== Changelog ==

= 1.0.0 =
* Initial release of Easy CSS Sync

== Upgrade Notice ==

= 1.0.0 =
* This is the first public release.