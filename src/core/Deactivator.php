<?php

/**
 * Plugin Deactivator
 *
 * @package FOMOZO
 * @since 0.1.0
 */

 namespace FOMOZO\Core;

 /**
  * Plugin deactivation handler
  */
 class Deactivator {
     
     /**
      * Deactivate the plugin
      */
     public static function deactivate() {
         self::clear_scheduled_events();
         
         // Flush rewrite rules
         flush_rewrite_rules();
         
         // Set deactivation flag
         update_option('fomozo_deactivated', current_time('mysql'));
     }
     
     /**
      * Clear any scheduled events
      */
     private static function clear_scheduled_events() {
         // Clear any wp-cron events
         wp_clear_scheduled_hook('fomozo_cleanup_analytics');
     }
 }
 