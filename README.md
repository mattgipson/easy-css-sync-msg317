# Easy CSS Sync

Automatically fetches custom CSS overrides from GitHub and enqueues them on your WordPress site. Includes optional webhook support for near-instant updates whenever you push changes to your GitHub repo.

---

## Features

- **Simple Setup**: Enter your GitHub raw CSS URL in the WordPress admin.
- **Private Repos**: Optionally provide a GitHub token for private repositories or to avoid rate limits.
- **Instant Webhook Sync**: Update your site’s CSS automatically whenever you commit to GitHub (optional).
- **Scheduled & Manual Updates**: Pull changes every hour via WP-Cron, or refresh CSS on-demand.

---

## Installation

1. **Upload or Install**  
   - Download the plugin files and place them in your `wp-content/plugins/` directory, or zip them and upload via “Plugins > Add New > Upload Plugin” in WordPress.
2. **Activate**  
   - In your WordPress admin, go to **Plugins > Installed Plugins** and activate **Easy CSS Sync**.

---

## Configuration

1. **Go to “Easy CSS Sync”** in the main WordPress admin menu.  
2. **Set GitHub Raw CSS URL**  
   - e.g. `https://raw.githubusercontent.com/<user>/<repo>/<branch>/styles.css`  
3. **GitHub Token** (optional)  
   - If your repo is private or you need to avoid rate limits, enter a personal access token.  
4. **Webhook Secret** (optional)  
   - For secure webhook validation when setting up GitHub Webhooks.

### Manual Refresh

- On the settings page, click **Refresh CSS Now** to immediately fetch the latest CSS from GitHub.

### Automatic Updates

- By default, Easy CSS Sync fetches updates **hourly** using WP-Cron.  
- (Optional) **GitHub Webhook**: With a configured webhook, changes will sync as soon as you push to your GitHub repo.

---

## GitHub Webhook Setup

1. **In WordPress**  
   - Enter your **Webhook Secret** in the plugin settings (a secure string).
2. **In GitHub**  
   - Go to **Settings > Webhooks** for your repo.  
   - Add a new webhook with:
     - **Payload URL**: `https://example.com/wp-json/easy-css-sync/v1/webhook`  
       (Replace `example.com` with your domain)  
     - **Content type**: `application/json`  
     - **Secret**: The same value you entered in your WordPress plugin settings.  
     - **Events**: You can select “Push” or any event that should trigger an update.  
   - Save the webhook.

---

## Security & Best Practices

- **Use HTTPS** for your WordPress site to ensure the secret is transmitted securely.
- For private repos, use a [GitHub personal access token](https://github.com/settings/tokens).
- Keep your WordPress core and all plugins updated for best security practices.

---

## FAQ

1. **Why isn’t my CSS updating?**  
   - Check that the **GitHub Raw URL** is valid and publicly accessible (or you have a valid token).  
   - If relying on WP-Cron, ensure cron is running. Some hosts disable WP-Cron.  
   - For webhooks, confirm you added the correct URL and secret in GitHub settings.

2. **Can I see when the CSS was last updated?**  
   - The plugin displays a “last synced” timestamp at the top of its settings page.

3. **Where is the CSS stored?**  
   - In a WordPress option. This means no external calls on every page load—only when you refresh or Cron triggers a fetch.

4. **Is the plugin GPL?**  
   - Yes. Easy CSS Sync is released under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).

---

## Contributing

Interested in contributing or submitting ideas? Feel free to open a pull request or file an issue on the [GitHub repository](#).

---

## License

This plugin is distributed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).

---