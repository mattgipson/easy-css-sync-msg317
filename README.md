# GitHub CSS Loader

A simple WordPress plugin that pulls a CSS file from a GitHub repository (via a raw URL) and enqueues it on your site. It supports automatic updates through:
- **WP-Cron** (periodic checks).
- **Manual refresh** from the admin settings.
- **GitHub Webhook** integration for instant updates when you push changes.

---

## Features

- **Easy Configuration**: Specify the GitHub raw CSS URL in the admin panel.  
- **Private Repo Support**: Optionally provide a GitHub token if your repo is private or if you want to avoid rate limits.  
- **Webhook Refresh**: Instantly update CSS whenever you push changes to GitHub, using a secure webhook.  
- **Scheduled & Manual**: Automatically update every hour (by default) via WP-Cron or refresh manually with a button.  
- **Simple Inline Enqueue**: The fetched CSS is stored locally (in a WordPress option) and then enqueued inline for speed and simplicity.

---

## Installation

1. **Download or Clone** this repository.  
2. Place the folder in your `wp-content/plugins/` directory, or zip it up and upload via your WordPress admin “Plugins > Add New > Upload Plugin.”  
3. Activate the plugin in **Plugins** → **Installed Plugins** in your WordPress dashboard.

---

## Configuration

1. In your WordPress admin, go to **GitHub CSS Loader** (found in your main menu after activation).
2. Enter the **GitHub Raw CSS URL**:  
   - Example: `https://raw.githubusercontent.com/<user>/<repo>/<branch>/style.css`
3. Optionally, add a **GitHub Token** (if needed):
   - This is useful for private repositories or to avoid GitHub’s rate-limiting on public repos.
4. (Optional) Provide a **GitHub Webhook Secret** for secure webhook calls.
5. Click **Save Changes**.

### Manual Refresh

- On the plugin settings page, you can click **"Refresh CSS Now"**.  
- The plugin will fetch the latest CSS from GitHub immediately and store it.

### WP-Cron Scheduled Refresh

- By default, the plugin fetches new CSS once every hour automatically.  
- If you want a different schedule, you can customize the `wp_schedule_event` call in the plugin code.

---

## GitHub Webhook Setup

To get instant CSS updates whenever you push to your GitHub repo:

1. **In WordPress**:
   - Go to **GitHub CSS Loader** → **Settings**.
   - Under **"GitHub Webhook Secret"**, set a secure, random secret string. You’ll use this in GitHub’s settings to help verify requests.
   - Save your changes.
   
2. **In GitHub**:
   - Go to your repository → **Settings** → **Webhooks**.
   - Click **"Add webhook"**.
   - **Payload URL**:  
     - Use the following format, replacing `example.com` with your domain:  
       `https://example.com/wp-json/ghcssloader/v1/webhook`
   - **Content Type**: `application/json`
   - **Secret**:  
     - Paste the exact same secret you used in your WordPress plugin settings.
   - **Event Triggers**:  
     - Select **"Just the push event"** (or any event that should trigger a CSS update).
   - Click **"Add webhook"** to save.

#### Testing the Webhook
- After saving, GitHub provides options to **“Ping”** or **“Redeliver”** the webhook.  
- You can also do a quick test by making a commit/push to your repo.  
- If everything is set up correctly, your site’s CSS will refresh immediately.

---

## Security Notes

- **Use HTTPS** (SSL/TLS) on your WordPress site so the secret and payload aren’t transmitted in plain text.  
- If using a private repo, set a **Personal Access Token** under “GitHub Token” to allow authenticated requests.  
- Always use a **strong Webhook Secret** when enabling the webhook feature, to prevent unauthorized triggers.

---

## FAQ

**1) What if my CSS doesn’t update automatically?**  
- Ensure your GitHub webhook is configured correctly.  
- Check the plugin settings to make sure the URL and token are correct.  
- Verify that WP-Cron is running on your site (some hosting environments disable it).

**2) Can I store the CSS as a file instead of inline?**  
- This plugin is designed to store CSS in the database for simplicity.  
- You can modify the code to save the file in `wp-content/uploads` or another location and enqueue it from there if you prefer.

**3) Does the plugin handle minification?**  
- Not by default. If you want minification, you can either commit a minified CSS file to GitHub or implement additional build steps before pushing.

---

## Contributing

Feel free to open issues or submit pull requests for improvements, bug fixes, or new features.

---

## License

This plugin is distributed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).

---