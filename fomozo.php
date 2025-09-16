<?php
/**
 * Plugin Name: FOMOZO
 * Plugin URI: https://fomozo.com
 * Description: Social Proof & FOMO Notifications for WordPress - Create urgency and boost conversions with strategic social proof notifications
 * Version: 0.1.0
 * Author: 38zo Team
 * Author URI: https://38zo.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: fomozo
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: false
 *
 * @package FOMOZO
 * @version 0.1.0
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FOMOZO_VERSION', '0.1.0');
define('FOMOZO_PLUGIN_FILE', __FILE__);
define('FOMOZO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FOMOZO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FOMOZO_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('FOMOZO_MIN_PHP_VERSION', '7.4');
define('FOMOZO_MIN_WP_VERSION', '5.0');

