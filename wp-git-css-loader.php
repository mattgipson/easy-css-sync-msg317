<?php
/**
 * Plugin Name: GitHub CSS Loader
 * Description: Pulls a CSS file from a GitHub repository and enqueues it on your WordPress site, with optional Webhook support for auto-refresh.
 * Version:     1.1.0
 * Author:      MSG317
 * License:     GPL-2.0+
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

class GitHub_CSS_Loader {

    private $option_name = 'gh_css_loader_options';
    private $cron_hook   = 'gh_css_loader_fetch_cron';

    public function __construct() {
        // Register settings on admin_init
        add_action('admin_init', [ $this, 'register_settings' ]);

        // Add settings page
        add_action('admin_menu', [ $this, 'add_admin_menu' ]);

        // Enqueue CSS on the front end
        add_action('wp_enqueue_scripts', [ $this, 'enqueue_css' ]);

        // Cron job
        add_action($this->cron_hook, [ $this, 'fetch_css_from_github' ]);

        // REST API endpoint for GitHub webhook
        add_action('rest_api_init', [ $this, 'register_webhook_endpoint' ]);

        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [ $this, 'activate_plugin' ]);
        register_deactivation_hook(__FILE__, [ $this, 'deactivate_plugin' ]);
    }

    /**
     * Register plugin settings fields
     */
    public function register_settings() {
        register_setting($this->option_name, $this->option_name);

        add_settings_section(
            'gh_css_loader_section',
            'GitHub CSS Loader Settings',
            function() {
                echo '<p>Configure your GitHub RAW URL, optional token, and webhook secret (if using webhooks).</p>';
            },
            $this->option_name
        );

        add_settings_field(
            'gh_raw_url',
            'GitHub Raw CSS URL',
            [ $this, 'raw_url_callback' ],
            $this->option_name,
            'gh_css_loader_section'
        );

        add_settings_field(
            'gh_token',
            'GitHub Token (optional)',
            [ $this, 'token_callback' ],
            $this->option_name,
            'gh_css_loader_section'
        );

        // NEW: GitHub Webhook Secret
        add_settings_field(
            'gh_webhook_secret',
            'GitHub Webhook Secret (optional)',
            [ $this, 'webhook_secret_callback' ],
            $this->option_name,
            'gh_css_loader_section'
        );
    }

    /**
     * Render the input field for raw URL
     */
    public function raw_url_callback() {
        $options = get_option($this->option_name);
        $gh_raw_url = isset($options['gh_raw_url']) ? esc_url($options['gh_raw_url']) : '';
        echo '<input type="text" name="' . $this->option_name . '[gh_raw_url]" value="' . $gh_raw_url . '" style="width: 100%;" placeholder="https://raw.githubusercontent.com/.../styles.css" />';
    }

    /**
     * Render the input field for GitHub token
     */
    public function token_callback() {
        $options = get_option($this->option_name);
        $gh_token = isset($options['gh_token']) ? sanitize_text_field($options['gh_token']) : '';
        echo '<input type="text" name="' . $this->option_name . '[gh_token]" value="' . $gh_token . '" style="width: 100%;" placeholder="Personal Access Token if needed" />';
    }

    /**
     * Render the input field for Webhook Secret
     */
    public function webhook_secret_callback() {
        $options = get_option($this->option_name);
        $gh_webhook_secret = isset($options['gh_webhook_secret']) ? sanitize_text_field($options['gh_webhook_secret']) : '';
        echo '<input type="text" name="' . $this->option_name . '[gh_webhook_secret]" value="' . $gh_webhook_secret . '" style="width: 100%;" placeholder="Webhook Secret for Security" />';
    }

    /**
     * Add a menu page for plugin settings
     */
    public function add_admin_menu() {
        add_menu_page(
            'GitHub CSS Loader',
            'GitHub CSS Loader',
            'manage_options',
            $this->option_name,
            [ $this, 'settings_page' ],
            'dashicons-art',
            100
        );
    }

    /**
     * Display settings form + a manual "Refresh" button
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>GitHub CSS Loader</h1>
            <form method="post" action="options.php">
                <?php 
                    settings_fields($this->option_name);
                    do_settings_sections($this->option_name);
                    submit_button();
                ?>
            </form>
            <hr/>
            <h2>Manual CSS Refresh</h2>
            <form method="post">
                <?php wp_nonce_field('gh_css_loader_manual_refresh'); ?>
                <input type="submit" name="gh_css_loader_refresh" class="button button-secondary" value="Refresh CSS Now"/>
            </form>
        </div>
        <?php
        
        // Handle manual refresh
        if ( isset($_POST['gh_css_loader_refresh']) ) {
            check_admin_referer('gh_css_loader_manual_refresh');
            $this->fetch_css_from_github();
            echo '<div class="updated"><p>CSS refreshed from GitHub!</p></div>';
        }
    }

    /**
     * Fetch the latest CSS from the GitHub Raw URL, store it in the options
     */
    public function fetch_css_from_github() {
        $options = get_option($this->option_name);
        if ( empty($options['gh_raw_url']) ) {
            return;
        }

        $args = [
            'timeout' => 20, 
            'headers' => []
        ];

        // If a token is set (for private repos or to avoid rate limiting)
        if ( ! empty($options['gh_token']) ) {
            $args['headers']['Authorization'] = 'token ' . $options['gh_token'];
        }

        $response = wp_remote_get($options['gh_raw_url'], $args);

        if ( is_wp_error($response) ) {
            error_log('GitHub CSS Loader - Error fetching CSS: ' . $response->get_error_message());
            return;
        }

        $css_body = wp_remote_retrieve_body($response);
        if ( ! empty($css_body) ) {
            // Save CSS in an option
            $options['gh_css_content'] = wp_kses_post($css_body);
            update_option($this->option_name, $options);
        }
    }

    /**
     * Enqueue the stored CSS on the frontend
     */
    public function enqueue_css() {
        $options = get_option($this->option_name);
        if ( ! empty($options['gh_css_content']) ) {
            wp_register_style('github-custom-css', false);
            wp_enqueue_style('github-custom-css');
            wp_add_inline_style('github-custom-css', $options['gh_css_content']);
        }
    }

    /**
     * Create a custom REST route for GitHub webhooks
     */
    public function register_webhook_endpoint() {
        register_rest_route(
            'ghcssloader/v1',    // namespace
            '/webhook',          // route
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_webhook' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * Handle GitHub webhook requests
     */
    public function handle_webhook( $request ) {
        $options = get_option($this->option_name);
        $secret  = isset($options['gh_webhook_secret']) ? $options['gh_webhook_secret'] : '';

        // If no secret is set, we skip verification (not recommended, but possible)
        if ( ! empty($secret) ) {
            // GitHub sends the signature in this header
            $github_signature = $request->get_header('x-hub-signature-256');
            $raw_payload      = $request->get_body();

            if ( empty($github_signature) ) {
                return new WP_Error('no_signature', 'Missing X-Hub-Signature-256 header.', [ 'status' => 400 ]);
            }

            // Compute HMAC using the secret
            $hash = 'sha256=' . hash_hmac('sha256', $raw_payload, $secret);

            if ( ! hash_equals($hash, $github_signature) ) {
                return new WP_Error('invalid_signature', 'Webhook signature does not match.', [ 'status' => 403 ]);
            }
        }

        // If we reach here, the signature is valid or there's no secret
        // Optionally, check the event type if you only want certain events
        // e.g. $github_event = $request->get_header('x-github-event');

        // Now fetch the latest CSS
        $this->fetch_css_from_github();

        // Return success
        return [
            'status'  => 'ok',
            'message' => 'CSS refreshed successfully.',
        ];
    }

    /**
     * Schedule cron job on plugin activation
     */
    public function activate_plugin() {
        if ( ! wp_next_scheduled($this->cron_hook) ) {
            wp_schedule_event(time(), 'hourly', $this->cron_hook);
        }
    }

    /**
     * Clear cron job on plugin deactivation
     */
    public function deactivate_plugin() {
        wp_clear_scheduled_hook($this->cron_hook);
    }
}

// Initialize plugin
new GitHub_CSS_Loader();
