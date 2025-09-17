<?php
/**
 * Plugin Name: FOMOZO
 * Description: Social Proof & FOMO Notifications for WordPress
 * Version: 0.1.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: fomozo
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package FOMOZO
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('FOMOZO_VERSION', '0.1.0');
define('FOMOZO_FILE', __FILE__);
define('FOMOZO_PATH', plugin_dir_path(__FILE__));
define('FOMOZO_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
final class FOMOZO_Plugin {
    
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Check requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Load autoloader
        $this->load_autoloader();
        
        // Initialize plugin
        add_action('plugins_loaded', [$this, 'load_plugin']);
    }
    
    /**
     * Check minimum requirements
     */
    private function check_requirements() {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>FOMOZO requires PHP 7.4 or higher.</p></div>';
            });
            return false;
        }
        return true;
    }
    
    /**
     * Load Composer autoloader
     */
    private function load_autoloader() {
        $autoloader = FOMOZO_PATH . 'vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        }
    }
    
    /**
     * Load main plugin functionality
     */
    public function load_plugin() {
        if (class_exists('FOMOZO\Core\Plugin')) {
            new \FOMOZO\Core\Plugin();
        }
    }
}

/**
 * Activation hook
 */
function fomozo_activate() {
    if (class_exists('FOMOZO\Core\Activator')) {
        \FOMOZO\Core\Activator::activate();
    }
}

/**
 * Deactivation hook
 */
function fomozo_deactivate() {
    if (class_exists('FOMOZO\Core\Deactivator')) {
        \FOMOZO\Core\Deactivator::deactivate();
    }
}

// Register hooks
register_activation_hook(__FILE__, 'fomozo_activate');
register_deactivation_hook(__FILE__, 'fomozo_deactivate');

// Initialize plugin
FOMOZO_Plugin::instance();
