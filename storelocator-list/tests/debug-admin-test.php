<?php
/**
 * Simple debug check for admin test page loading
 * 
 * Add this to your wp-config.php temporarily to debug:
 * include_once(ABSPATH . 'wp-content/plugins/storelocator-list/tests/debug-admin-test.php');
 */

// Only run after WordPress is loaded
if (!function_exists('is_admin')) {
    return;
}

// Only run in admin
if (!is_admin()) {
    return;
}

// Add admin notice to show debug info
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo '<div class="notice notice-info">';
    echo '<h3>Store Update Debug Info</h3>';
    
    // Check WP_DEBUG
    echo '<p><strong>WP_DEBUG:</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . '</p>';
    
    // Check if classes exist
    $classes = [
        'SLList_Store_Update_Test',
        'StoreLocatorList\PublicPages\SLList_Store_Update',
        'StoreLocatorList\Core\SLList_Security'
    ];
    
    echo '<p><strong>Classes loaded:</strong></p><ul>';
    foreach ($classes as $class) {
        $exists = class_exists($class);
        echo '<li>' . $class . ': ' . ($exists ? '✓ Loaded' : '✗ Not found') . '</li>';
    }
    echo '</ul>';
    
    // Check file paths
    $plugin_path = plugin_dir_path(dirname(__FILE__)) . 'storelocator-list/';
    $test_file = plugin_dir_path(dirname(__FILE__)) . 'tests/admin-test-page.php';
    
    echo '<p><strong>File paths:</strong></p><ul>';
    echo '<li>Plugin path: ' . $plugin_path . '</li>';
    echo '<li>Test file: ' . $test_file . ' (' . (file_exists($test_file) ? 'Exists' : 'Not found') . ')</li>';
    echo '</ul>';
    
    // Show direct test links
    echo '<p><strong>Direct test links:</strong></p>';
    echo '<p><a href="' . home_url('/wp-content/plugins/storelocator-list/tests/test-store-update.php') . '" target="_blank" class="button">Browser Test Page</a></p>';
    
    echo '</div>';
});
?>
