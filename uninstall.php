<?php
// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Respect the setting: remove data on uninstall
$remove = get_option( 'fomozo_remove_data_on_uninstall', 0 );
if ( ! $remove ) {
    return;
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

foreach ( $options as $opt ) {
    delete_option( $opt );
}

// Drop tables
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'fomozo_campaigns' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'fomozo_analytics' );


