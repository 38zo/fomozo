<?php
/**
 * Test script to verify table creation and campaign saving works
 * 
 * This file should be deleted after testing
 */

// Load WordPress
require_once '../../../wp-load.php';

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied. Admin privileges required.' );
}

echo "<h1>FOMOZO Table Test</h1>";

// Test DatabaseManager
$db_manager = new \FOMOZO\Database\DatabaseManager();

echo "<h2>1. Testing Table Existence Check</h2>";
$tables_exist = $db_manager->tablesExist();
echo "Tables exist: " . ( $tables_exist ? 'YES' : 'NO' ) . "<br>";

if ( ! $tables_exist ) {
    echo "<h2>2. Creating Tables</h2>";
    $created = $db_manager->ensureTablesExist();
    echo "Tables created: " . ( $created ? 'YES' : 'NO' ) . "<br>";
    
    if ( $created ) {
        echo "Tables now exist: " . ( $db_manager->tablesExist() ? 'YES' : 'NO' ) . "<br>";
    }
}

echo "<h2>3. Testing Campaign Creation</h2>";
$test_campaign = [
    'name' => 'Test Campaign ' . time(),
    'type' => 'sales',
    'status' => 'active',
    'settings' => wp_json_encode( [
        'template' => 'bottom-left',
        'message_template' => '{customer} from {location} purchased {product} {time}',
        'delay' => 3000,
        'duration' => 5000,
        'display_rules' => [ 'sitewide' => true ],
        'anonymize' => true,
        'integration' => 'woocommerce',
        'campaign_subtype' => 'sales',
        'audience' => 'everyone'
    ] )
];

$campaign_id = $db_manager->saveCampaign( $test_campaign );
echo "Campaign created with ID: " . $campaign_id . "<br>";

if ( $campaign_id ) {
    echo "<h2>4. Testing Campaign Retrieval</h2>";
    $retrieved = $db_manager->getCampaign( $campaign_id );
    echo "Campaign retrieved: " . ( $retrieved ? 'YES' : 'NO' ) . "<br>";
    
    if ( $retrieved ) {
        echo "Campaign name: " . esc_html( $retrieved->name ) . "<br>";
        echo "Campaign type: " . esc_html( $retrieved->type ) . "<br>";
    }
    
    echo "<h2>5. Testing Campaign Deletion</h2>";
    $deleted = $db_manager->deleteCampaign( $campaign_id );
    echo "Campaign deleted: " . ( $deleted ? 'YES' : 'NO' ) . "<br>";
}

echo "<h2>6. Testing All Campaigns</h2>";
$all_campaigns = $db_manager->getCampaigns();
echo "Total campaigns: " . count( $all_campaigns ) . "<br>";

echo "<h2>Test Complete!</h2>";
echo "<p><strong>If all tests passed, the table issue is fixed!</strong></p>";
echo "<p><em>Remember to delete this test file after testing.</em></p>";
?>
