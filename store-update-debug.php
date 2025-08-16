<?php
/**
 * Plugin Name: Store Update Debug Helper
 * Description: Temporary debug plugin to test store update functionality
 * Version: 1.0
 * 
 * This is a standalone plugin file for debugging. Just activate it in WordPress admin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only run in admin
add_action('admin_init', function() {
    // Add admin notice with debug info
    add_action('admin_notices', function() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo '<div class="notice notice-info">';
        echo '<h3>🔧 Store Update Debug Info</h3>';
        
        // Check WP_DEBUG
        echo '<p><strong>WP_DEBUG:</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled') . '</p>';
        
        // Check if main plugin is active
        $plugin_active = is_plugin_active('storelocator-list/storelocator-list.php');
        echo '<p><strong>Main Plugin:</strong> ' . ($plugin_active ? '✅ Active' : '❌ Inactive') . '</p>';
        
        // Check if classes exist
        $classes = [
            'SLList_Store_Update_Test' => 'Admin Test Page Class',
            'StoreLocatorList\PublicPages\SLList_Store_Update' => 'Store Update Class',
            'StoreLocatorList\Core\SLList_Security' => 'Security Class',
            'StoreLocatorList\SLList_Plugin' => 'Main Plugin Class'
        ];
        
        echo '<p><strong>Classes loaded:</strong></p><ul>';
        foreach ($classes as $class => $description) {
            $exists = class_exists($class);
            echo '<li><strong>' . $description . ':</strong> ' . ($exists ? '✅ Loaded' : '❌ Not found') . '</li>';
        }
        echo '</ul>';
        
        // Check file paths
        $plugin_path = WP_PLUGIN_DIR . '/storelocator-list/';
        $test_files = [
            'storelocator-list/includes/public/class-sllist-store-update.php' => 'Store Update Class File',
            'tests/admin-test-page.php' => 'Admin Test Page File',
            'tests/test-store-update.php' => 'Browser Test Page File'
        ];
        
        echo '<p><strong>File status:</strong></p><ul>';
        foreach ($test_files as $file => $description) {
            $full_path = WP_PLUGIN_DIR . '/storelocator-list/' . $file;
            $exists = file_exists($full_path);
            echo '<li><strong>' . $description . ':</strong> ' . ($exists ? '✅ Found' : '❌ Missing') . '</li>';
        }
        echo '</ul>';
        
        // Show test URLs
        echo '<p><strong>Test URLs:</strong></p>';
        echo '<p>';
        echo '<a href="' . home_url('/wp-content/plugins/storelocator-list/tests/test-store-update.php') . '" target="_blank" class="button button-primary">🧪 Browser Test Page</a> ';
        echo '<a href="' . home_url('/store-manager/') . '" target="_blank" class="button button-secondary">🏪 Store Manager</a> ';
        echo '<a href="' . home_url('/update-store/') . '" target="_blank" class="button button-secondary">📝 Update Store (no token)</a>';
        echo '</p>';
        
        // Manual admin test menu creation
        if (!class_exists('SLList_Store_Update_Test')) {
            echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px;">';
            echo '<p><strong>⚠️ Admin Test Class Not Found</strong></p>';
            echo '<p>The admin test page class is not loading. Try the browser test page instead.</p>';
            echo '</div>';
        }
        
        echo '</div>';
    });
});

// Try to manually load the admin test if it's not already loaded
add_action('plugins_loaded', function() {
    if (defined('WP_DEBUG') && WP_DEBUG && !class_exists('SLList_Store_Update_Test')) {
        $test_file = WP_PLUGIN_DIR . '/storelocator-list/tests/admin-test-page.php';
        if (file_exists($test_file)) {
            require_once $test_file;
        }
    }
});
?>
