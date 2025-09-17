<?php
/**
 * Plugin Activator
 *
 * @package FOMOZO
 * @since 0.1.0
 */

namespace FOMOZO\Core;

/**
 * Plugin activation handler
 */
class Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::create_pages();
        
        // Set activation flag
        update_option('fomozo_activated', true);
        update_option('fomozo_version', FOMOZO_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Campaigns table
        $campaigns_table = $wpdb->prefix . 'fomozo_campaigns';
        $campaigns_sql = "CREATE TABLE $campaigns_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            settings longtext,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";
        
        // Analytics table
        $analytics_table = $wpdb->prefix . 'fomozo_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            campaign_id int(11) NOT NULL,
            type varchar(50) NOT NULL,
            user_ip varchar(45),
            user_agent text,
            page_url varchar(500),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($campaigns_sql);
        dbDelta($analytics_sql);
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = [
            'fomozo_enable_sound' => false,
            'fomozo_animation_speed' => 500,
            'fomozo_default_delay' => 3000,
            'fomozo_default_duration' => 5000,
            'fomozo_anonymize_users' => true
        ];
        
        foreach ($defaults as $option => $value) {
            add_option($option, $value);
        }
    }
    
    /**
     * Create necessary pages if needed
     */
    private static function create_pages() {
        // For MVP, no special pages needed
        // Later: privacy policy additions, opt-out pages, etc.
    }
}
