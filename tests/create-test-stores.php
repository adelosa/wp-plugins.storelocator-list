<?php
/**
 * Test Store Creation Script
 * 
 * This script creates a few test stores to test the search functionality.
 * Run this once to populate your test database with sample stores.
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this script.');
}

echo "<h1>Creating Test Stores</h1>";

// Test stores data
$test_stores = array(
    array(
        'title' => 'Downtown Coffee Shop',
        'email' => 'info@downtowncoffee.com',
        'phone' => '(555) 123-4567',
        'address' => '123 Main Street',
        'address2' => 'Suite 100',
        'city' => 'Sydney',
        'state' => 'NSW',
        'zip' => '2000',
        'country' => 'Australia',
        'description' => 'Cozy coffee shop in the heart of downtown Sydney with fresh roasted beans and homemade pastries.'
    ),
    array(
        'title' => 'Seaside Hardware Store',
        'email' => 'manager@seasidehardware.com.au',
        'phone' => '(555) 987-6543',
        'address' => '456 Ocean Drive',
        'address2' => '',
        'city' => 'Bondi',
        'state' => 'NSW', 
        'zip' => '2026',
        'country' => 'Australia',
        'description' => 'Full-service hardware store serving the Bondi community for over 30 years.'
    ),
    array(
        'title' => 'Green Valley Pharmacy',
        'email' => 'contact@greenvalleypharmacy.com',
        'phone' => '(555) 555-0123',
        'address' => '789 Garden Road',
        'address2' => '',
        'city' => 'Melbourne',
        'state' => 'VIC',
        'zip' => '3000',
        'country' => 'Australia',
        'description' => 'Trusted neighborhood pharmacy providing healthcare services and prescription medications.'
    ),
    array(
        'title' => 'Tech Solutions Brisbane',
        'email' => 'support@techsolutions.bne.com',
        'phone' => '(555) 777-8888',
        'address' => '321 Innovation Street',
        'address2' => 'Level 5',
        'city' => 'Brisbane',
        'state' => 'QLD',
        'zip' => '4000',
        'country' => 'Australia',
        'description' => 'Leading technology solutions provider specializing in business IT support and consulting.'
    ),
    array(
        'title' => 'Mountain View Bakery',
        'email' => 'orders@mountainviewbakery.com.au',
        'phone' => '(555) 333-2222',
        'address' => '654 Hill Top Avenue',
        'address2' => '',
        'city' => 'Adelaide', 
        'state' => 'SA',
        'zip' => '5000',
        'country' => 'Australia',
        'description' => 'Artisan bakery creating fresh bread, cakes, and pastries daily using traditional methods.'
    )
);

$created_count = 0;
$errors = array();

foreach ($test_stores as $store_data) {
    // Create the store post
    $post_data = array(
        'post_title' => $store_data['title'],
        'post_content' => $store_data['description'],
        'post_status' => 'publish',
        'post_type' => 'wpsl_stores'
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        $errors[] = "Failed to create store: " . $store_data['title'] . " - " . $post_id->get_error_message();
        continue;
    }
    
    // Add store meta fields
    $meta_fields = array(
        'wpsl_email' => $store_data['email'],
        'wpsl_phone' => $store_data['phone'],
        'wpsl_address' => $store_data['address'],
        'wpsl_address2' => $store_data['address2'],
        'wpsl_city' => $store_data['city'],
        'wpsl_state' => $store_data['state'],
        'wpsl_zip' => $store_data['zip'],
        'wpsl_country' => $store_data['country'],
        'wpsl_lat' => '', // Would normally be populated by geocoding
        'wpsl_lng' => ''  // Would normally be populated by geocoding
    );
    
    foreach ($meta_fields as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }
    
    $created_count++;
    echo "<p>✅ Created store: <strong>" . esc_html($store_data['title']) . "</strong> (ID: $post_id)</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Successfully created:</strong> $created_count stores</p>";

if (!empty($errors)) {
    echo "<p><strong>Errors:</strong></p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: red;'>" . esc_html($error) . "</li>";
    }
    echo "</ul>";
}

// Count total stores now
$store_count = wp_count_posts('wpsl_stores');
echo "<p><strong>Total stores in database:</strong> " . $store_count->publish . " published</p>";

echo "<hr>";
echo "<h2>Test Your Store Search</h2>";
echo "<p>Now you can test the store search functionality:</p>";
echo "<ul>";
echo "<li><a href='" . home_url('/?sllist_page=store_manager') . "' target='_blank'>Store Manager Page (Query URL)</a></li>";
echo "<li><a href='" . home_url('/store-manager/') . "' target='_blank'>Store Manager Page (Pretty URL)</a></li>";
echo "</ul>";

echo "<h3>Try these searches:</h3>";
echo "<ul>";
echo "<li><strong>\"coffee\"</strong> - Should find Downtown Coffee Shop</li>";
echo "<li><strong>\"seaside\"</strong> - Should find Seaside Hardware Store</li>";
echo "<li><strong>\"sydney\"</strong> - Should find stores in Sydney</li>";
echo "<li><strong>\"(555) 123\"</strong> - Should find stores with matching phone numbers</li>";
echo "<li><strong>\"@.com\"</strong> - Should find stores with .com email addresses</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>You can run this script multiple times - it will create new stores each time.</em></p>";
echo "<p><strong>To remove test stores:</strong> Go to WordPress Admin → WP Store Locator → All Stores and delete them manually.</p>";

?>
