<?php
/**
 * Plugin Name: Easy CSS Sync
 * Plugin URI:  https://msg317.com
 * Description: Bypass the theme’s "Additional CSS" field by automatically fetching custom CSS overrides from GitHub. Includes optional webhook support for instant updates.
 * Version:     1.0.0
 * Text Domain: easy-css-sync
 * Author:      MSG317
 * Author URI:  https://msg317.com
 * License:     GPL-2.0+
 */

// Security check to prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Easy_CSS_Sync {

    private $option_name = 'easy_css_sync_options';
    private $cron_hook   = 'easy_css_sync_cron_hook';

    public function __construct() {
        // Register plugin settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Add settings page
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

        // Enqueue CSS on the front end
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_css' ] );

        // Cron job
        add_action( $this->cron_hook, [ $this, 'fetch_css_from_github' ] );

        // REST API endpoint for GitHub webhook
        add_action( 'rest_api_init', [ $this, 'register_webhook_endpoint' ] );

        // Activation/Deactivation hooks
        register_activation_hook( __FILE__, [ $this, 'activate_plugin' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate_plugin' ] );
    }

    /**
     * Register plugin settings fields with a sanitization callback
     */
    public function register_settings() {
        register_setting(
            $this->option_name,
            $this->option_name,
            [
                // Plain string reference to a standalone function
                'sanitize_callback' => 'easy_css_sync_sanitize_options',
            ]
        );

        add_settings_section(
            'easy_css_sync_section',
            'Easy CSS Sync Settings',
            function() {
                echo '<p>Configure your GitHub RAW URL, optional token, and webhook secret.</p>';
            },
            $this->option_name
        );

        add_settings_field(
            'gh_raw_url',
            'GitHub Raw CSS URL',
            [ $this, 'raw_url_callback' ],
            $this->option_name,
            'easy_css_sync_section'
        );

        add_settings_field(
            'gh_token',
            'GitHub Token (optional)',
            [ $this, 'token_callback' ],
            $this->option_name,
            'easy_css_sync_section'
        );

        add_settings_field(
            'gh_webhook_secret',
            'GitHub Webhook Secret (optional)',
            [ $this, 'webhook_secret_callback' ],
            $this->option_name,
            'easy_css_sync_section'
        );
    }

    /**
     * Render the input field for GitHub Raw CSS URL
     */
    public function raw_url_callback() {
        $options    = get_option( $this->option_name );
        $gh_raw_url = isset( $options['gh_raw_url'] ) ? $options['gh_raw_url'] : '';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( $this->option_name ); ?>[gh_raw_url]"
            value="<?php echo esc_attr( $gh_raw_url ); ?>"
            style="width: 100%;"
            placeholder="<?php echo esc_attr( 'https://raw.githubusercontent.com/.../styles.css' ); ?>"
        />
        <?php
    }

    /**
     * Render the input field for GitHub Token
     */
    public function token_callback() {
        $options  = get_option( $this->option_name );
        $gh_token = isset( $options['gh_token'] ) ? $options['gh_token'] : '';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( $this->option_name ); ?>[gh_token]"
            value="<?php echo esc_attr( $gh_token ); ?>"
            style="width: 100%;"
            placeholder="<?php echo esc_attr( 'Personal Access Token if needed' ); ?>"
        />
        <?php
    }

    /**
     * Render the input field for GitHub Webhook Secret
     */
    public function webhook_secret_callback() {
        $options           = get_option( $this->option_name );
        $gh_webhook_secret = isset( $options['gh_webhook_secret'] ) ? $options['gh_webhook_secret'] : '';
        ?>
        <input
            type="text"
            name="<?php echo esc_attr( $this->option_name ); ?>[gh_webhook_secret]"
            value="<?php echo esc_attr( $gh_webhook_secret ); ?>"
            style="width: 100%;"
            placeholder="<?php echo esc_attr( 'Webhook Secret for Security' ); ?>"
        />
        <?php
    }

    /**
     * Add a menu page for plugin settings
     */
    public function add_admin_menu() {
        add_menu_page(
            'Easy CSS Sync',
            'Easy CSS Sync',
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
        // Manual refresh check
        if ( isset( $_POST['easy_css_sync_refresh'] ) ) {
            check_admin_referer( 'easy_css_sync_manual_refresh' );
            $this->fetch_css_from_github();
            echo '<div class="updated"><p>CSS refreshed from GitHub!</p></div>';
        }

        $options      = get_option( $this->option_name );
        $last_updated = ! empty( $options['last_updated'] ) ? $options['last_updated'] : 'Never';

        if ( 'Never' !== $last_updated ) {
            $readable_time = date_i18n(
                get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
                strtotime( $last_updated )
            );
        } else {
            $readable_time = $last_updated;
        }
        ?>
        <div class="wrap">
            <h1>Easy CSS Sync</h1>
            <p><em>CSS last synced: <?php echo esc_html( $readable_time ); ?></em></p>

            <form method="post" action="options.php">
                <?php
                    settings_fields( $this->option_name );
                    do_settings_sections( $this->option_name );
                    submit_button();
                ?>
            </form>

            <hr/>
            <h2>Manual CSS Refresh</h2>
            <form method="post">
                <?php wp_nonce_field( 'easy_css_sync_manual_refresh' ); ?>
                <button type="submit" name="easy_css_sync_refresh" class="button button-secondary">
                    Refresh CSS Now
                </button>
                <span class="spinner" style="vertical-align:middle;display:none;"></span>
            </form>
        </div>

        <script>
        (function($){
            $(document).ready(function(){
                // Show spinner when the manual refresh form is submitted
                $('form').on('submit', function(){
                    $(this).find('.spinner').show();
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Fetch the latest CSS from the GitHub Raw URL, store it in the options
     */
    public function fetch_css_from_github() {
        $options = get_option( $this->option_name );
        if ( empty( $options['gh_raw_url'] ) ) {
            return;
        }

        $args = [
            'timeout' => 20,
            'headers' => [],
        ];

        if ( ! empty( $options['gh_token'] ) ) {
            $args['headers']['Authorization'] = 'token ' . $options['gh_token'];
        }

        $response = wp_remote_get( $options['gh_raw_url'], $args );

        if ( is_wp_error( $response ) ) {
            // Debug logging removed to avoid warnings.
            return;
        }

        $css_body = wp_remote_retrieve_body( $response );
        if ( ! empty( $css_body ) ) {
            $options['gh_css_content'] = wp_kses_post( $css_body );
            $options['last_updated']    = current_time( 'mysql' );
            update_option( $this->option_name, $options );
        }
    }

    /**
     * Enqueue the stored CSS on the frontend
     */
    public function enqueue_css() {
        $options = get_option( $this->option_name );
        if ( ! empty( $options['gh_css_content'] ) ) {
            // Optional: use last_updated as a version for cache-busting
            $version = ! empty( $options['last_updated'] )
                ? strtotime( $options['last_updated'] )
                : null;

            wp_register_style(
                'easy-css-sync',
                false,
                [],
                $version
            );

            wp_enqueue_style( 'easy-css-sync' );
            wp_add_inline_style( 'easy-css-sync', $options['gh_css_content'] );
        }
    }

    /**
     * Create a custom REST route for GitHub webhooks
     */
    public function register_webhook_endpoint() {
        register_rest_route(
            'easy-css-sync/v1',
            '/webhook',
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
        $options = get_option( $this->option_name );
        $secret  = isset( $options['gh_webhook_secret'] ) ? $options['gh_webhook_secret'] : '';

        if ( ! empty( $secret ) ) {
            $github_signature = $request->get_header( 'x-hub-signature-256' );
            $raw_payload      = $request->get_body();

            if ( empty( $github_signature ) ) {
                return new WP_Error( 'no_signature', 'Missing X-Hub-Signature-256 header.', [ 'status' => 400 ] );
            }

            $hash = 'sha256=' . hash_hmac( 'sha256', $raw_payload, $secret );
            if ( ! hash_equals( $hash, $github_signature ) ) {
                return new WP_Error( 'invalid_signature', 'Webhook signature does not match.', [ 'status' => 403 ] );
            }
        }

        $this->fetch_css_from_github();
        return [
            'status'  => 'ok',
            'message' => 'CSS refreshed successfully.',
        ];
    }

    /**
     * Schedule cron job on plugin activation
     */
    public function activate_plugin() {
        if ( ! wp_next_scheduled( $this->cron_hook ) ) {
            wp_schedule_event( time(), 'hourly', $this->cron_hook );
        }
    }

    /**
     * Clear cron job on plugin deactivation
     */
    public function deactivate_plugin() {
        wp_clear_scheduled_hook( $this->cron_hook );
    }
}

// Initialize the plugin
new Easy_CSS_Sync();

/**
 * ADD "SETTINGS" LINK ON THE PLUGINS PAGE
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'easy_css_sync_plugin_action_links' );
function easy_css_sync_plugin_action_links( $links ) {
    // This slug should match the one used in add_menu_page(): $this->option_name
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( admin_url( 'admin.php?page=easy_css_sync_options' ) ),
        __( 'Settings', 'easy-css-sync' )
    );
    array_unshift( $links, $settings_link );
    return $links;
}

/**
 * STANDALONE SANITIZATION FUNCTION
 * (Fixes the dynamic argument warning in register_setting())
 */
function easy_css_sync_sanitize_options( $input ) {
    $output = [];

    if ( isset( $input['gh_raw_url'] ) ) {
        $output['gh_raw_url'] = esc_url_raw( $input['gh_raw_url'] );
    }
    if ( isset( $input['gh_token'] ) ) {
        $output['gh_token'] = sanitize_text_field( $input['gh_token'] );
    }
    if ( isset( $input['gh_webhook_secret'] ) ) {
        $output['gh_webhook_secret'] = sanitize_text_field( $input['gh_webhook_secret'] );
    }

    return $output;
}