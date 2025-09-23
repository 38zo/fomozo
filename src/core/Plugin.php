<?php
/**
 * Core Plugin Class
 *
 * @package FOMOZO
 * @since 0.1.0
 */

namespace FOMOZO\Core;

use FOMOZO\Admin\AdminInterface;
use FOMOZO\Frontend\DisplayManager;
use FOMOZO\Database\DatabaseManager;
use FOMOZO\Integrations\IntegrationManager;
use FOMOZO\Integrations\WooCommerce\WooCommerceIntegration;

/**
 * Main plugin core class
 */
class Plugin {
    
    /**
     * Plugin version
     */
    const VERSION = '0.1.0';
    
    /**
     * Minimum PHP version
     */
    const MIN_PHP_VERSION = '7.4';
    
    /**
     * Plugin components
     */
    private $admin;
    private $frontend;
    private $database;
    private $integrations;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX hooks for frontend
        add_action('wp_ajax_fomozo_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_nopriv_fomozo_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_fomozo_track_impression', [$this, 'ajax_track_impression']);
        add_action('wp_ajax_nopriv_fomozo_track_impression', [$this, 'ajax_track_impression']);

        // Admin cleanup endpoint
        add_action('wp_ajax_fomozo_wipe_data', [$this, 'ajax_wipe_data']);
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize database
        $this->database = new DatabaseManager();
        
        // Initialize admin interface
        if (is_admin()) {
            $this->admin = new AdminInterface();
        }
        
        // Initialize frontend display (only if not admin)
        if (!is_admin()) {
            $this->frontend = new DisplayManager();
        }

        // Initialize integrations and register built-ins via action so any manager instance sees them
        add_action('fomozo_integrations_register', function($manager) {
            // Register built-in WooCommerce integration
            $manager->register(new WooCommerceIntegration());
        });
        $this->integrations = new IntegrationManager();
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'fomozo',
            false,
            dirname(plugin_basename(FOMOZO_FILE)) . '/languages'
        );
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on pages where notifications might display
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'fomozo-popup',
            FOMOZO_URL . 'assets/css/popup.css',
            [],
            self::VERSION
        );
        
        wp_enqueue_script(
            'fomozo-frontend',
            FOMOZO_URL . 'assets/js/frontend.js',
            ['jquery'],
            self::VERSION,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('fomozo-frontend', 'fomozo_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fomozo_nonce'),
            'settings' => $this->get_frontend_settings()
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on FOMOZO admin pages
        if (!$this->is_fomozo_admin_page($hook)) {
            return;
        }
        
        wp_enqueue_style(
            'fomozo-admin',
            FOMOZO_URL . 'assets/css/admin.css',
            [],
            self::VERSION
        );
        
        wp_enqueue_script(
            'fomozo-admin',
            FOMOZO_URL . 'assets/js/admin.js',
            ['jquery'],
            self::VERSION,
            true
        );
        
        wp_localize_script('fomozo-admin', 'fomozo_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fomozo_admin_nonce')
        ]);
    }
    
    /**
     * Check if we should load frontend assets
     */
    private function should_load_frontend_assets() {
        // Get active campaigns
        $campaigns = $this->get_active_campaigns();
        
        if (empty($campaigns)) {
            return false;
        }
        
        // Check if current page matches any campaign display rules
        foreach ($campaigns as $campaign) {
            if ($this->campaign_should_display($campaign)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if current admin page is FOMOZO page
     */
    private function is_fomozo_admin_page($hook) {
        $fomozo_pages = [
            'toplevel_page_fomozo',
            'fomozo_page_fomozo-campaigns',
            'fomozo_page_fomozo-settings'
        ];
        
        return in_array($hook, $fomozo_pages, true);
    }
    
    /**
     * Get frontend settings for JavaScript
     */
    private function get_frontend_settings() {
        return [
            'enable_sound' => get_option('fomozo_enable_sound', false),
            'animation_speed' => get_option('fomozo_animation_speed', 500),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'gap_ms' => (int) get_option('fomozo_gap_ms', 4000)
        ];
    }
    
    /**
     * Get active campaigns
     */
    private function get_active_campaigns() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fomozo_campaigns';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = %s AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW())",
            'active'
        ));
    }
    
    /**
     * Check if campaign should display on current page
     */
    private function campaign_should_display($campaign) {
        $settings = json_decode($campaign->settings, true);
        $display_rules = $settings['display_rules'] ?? [];
        
        // For MVP: only sitewide display
        return !empty($display_rules['sitewide']);
    }
    
    /**
     * AJAX handler: Get notifications for frontend
     */
    public function ajax_get_notifications() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'fomozo_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $notifications = $this->generate_notifications();
        
        wp_send_json_success($notifications);
    }
    
    /**
     * AJAX handler: Track impression
     */
    public function ajax_track_impression() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'fomozo_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        
        if ($campaign_id) {
            $this->track_impression($campaign_id);
        }
        
        wp_send_json_success();
    }
    
    /**
     * Generate notifications based on active campaigns
     */
    private function generate_notifications() {
        $campaigns = $this->get_active_campaigns();
        $notifications = [];
        
        // If demo is enabled, generate internal demo notifications; otherwise rely on integrations
        if (get_option('fomozo_enable_demo_data', 0)) {
            foreach ($campaigns as $campaign) {
                if ($campaign->type === 'sales') {
                    $maybe = $this->generate_sales_notification($campaign);
                    if ($maybe) { $notifications[] = $maybe; }
                }
            }
        }
        // Allow integrations to provide notifications (e.g., WooCommerce)
        $external = apply_filters('fomozo_external_notifications', [], $campaigns);
        if (is_array($external)) {
            $notifications = array_merge($notifications, $external);
        }

        return array_filter($notifications);
    }
    
    /**
     * Generate sales notification
     */
    private function generate_sales_notification($campaign) {
        $settings = json_decode($campaign->settings, true);
        
        // Demo generator only when explicitly enabled
        $recent_sale = null;
        if (get_option('fomozo_enable_demo_data', 0)) {
            $recent_sale = $this->get_recent_sale($settings);
        }
        
        if (!$recent_sale) {
            return null;
        }
        
        return [
            'id' => $campaign->id,
            'type' => 'sales',
            'template' => $settings['template'] ?? 'bottom-left',
            'message' => $this->format_sales_message($recent_sale, $settings),
            'delay' => $settings['delay'] ?? 3000,
            'duration' => $settings['duration'] ?? 5000,
            'settings' => $settings
        ];
    }
    
    /**
     * Get recent sale data
     */
    private function get_recent_sale($settings) {
        // For MVP: generate demo data
        // Later: integrate with WooCommerce/EDD
        
        $demo_products = [
            'WordPress Theme',
            'SEO Plugin',
            'Contact Form Plugin',
            'Backup Plugin',
            'Security Plugin'
        ];
        
        $demo_locations = [
            'New York', 'London', 'Paris', 'Tokyo', 'Sydney',
            'Toronto', 'Berlin', 'Madrid', 'Amsterdam', 'Dublin'
        ];
        
        return [
            'product' => $demo_products[array_rand($demo_products)],
            'location' => $demo_locations[array_rand($demo_locations)],
            'time' => rand(1, 30) . ' minutes ago',
            // Always respect current global option dynamically
            'customer' => $this->generate_anonymous_name(get_option('fomozo_anonymize_users', true))
        ];
    }
    
    /**
     * Format sales message
     */
    private function format_sales_message($sale, $settings) {
        $template = $settings['message_template'] ?? '{customer} from {location} purchased {product} {time}';
        
        return str_replace(
            ['{customer}', '{location}', '{product}', '{time}'],
            [$sale['customer'], $sale['location'], $sale['product'], $sale['time']],
            $template
        );
    }
    
    /**
     * Generate anonymous customer name
     */
    private function generate_anonymous_name($anonymize = true) {
        if (!$anonymize) {
            $first_names = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Lisa'];
            $last_names = ['Smith', 'Johnson', 'Brown', 'Davis', 'Wilson', 'Moore'];
            return $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
        }
        
        $first_names = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Lisa'];
        return $first_names[array_rand($first_names)] . ' D.';
    }
    
    /**
     * Track impression for analytics
     */
    private function track_impression($campaign_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fomozo_analytics';
        
        $wpdb->insert($table, [
            'campaign_id' => $campaign_id,
            'type' => 'impression',
            'user_ip' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'page_url' => $_POST['page_url'] ?? '',
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Get user IP address (privacy-compliant)
     */
    private function get_user_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Anonymize IP for privacy
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_parts = explode('.', $ip);
            $ip_parts[3] = '0'; // Anonymize last octet
            return implode('.', $ip_parts);
        }
        
        return $ip;
    }

    /**
     * AJAX: Wipe all plugin data (admin only)
     */
    public function ajax_wipe_data() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'fomozo'));
        }
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'fomozo_admin_nonce')) {
            wp_send_json_error(__('Invalid nonce', 'fomozo'));
        }

        global $wpdb;
        // Delete options
        $options = [
            'fomozo_enable_sound',
            'fomozo_animation_speed',
            'fomozo_default_delay',
            'fomozo_default_duration',
            'fomozo_anonymize_users',
            'fomozo_gap_ms',
            'fomozo_activated',
            'fomozo_version',
            'fomozo_integrations_active',
            'fomozo_remove_data_on_uninstall'
        ];
        foreach ($options as $opt) { delete_option($opt); }

        // Drop tables
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'fomozo_campaigns');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'fomozo_analytics');

        wp_send_json_success();
    }
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        static $instance = null;
        
        if (null === $instance) {
            $instance = new self();
        }
        
        return $instance;
    }
}
