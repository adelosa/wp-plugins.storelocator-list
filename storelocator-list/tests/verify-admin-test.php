<?php
/**
 * Quick test to verify admin test page is loading
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/verify-admin-test.php
 */

// WordPress environment
$wp_load_paths = [
    dirname(__FILE__) . '/../../../../wp-load.php',
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded || !defined('ABSPATH')) {
    die('<h1>WordPress Not Found</h1><p>Could not load WordPress. Please access this file through your WordPress installation.</p>');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('<h1>Access Denied</h1><p>You must be an administrator to run this script. Please <a href="' . wp_login_url() . '">login</a> first.</p>');
}

echo "<h1>Admin Test Page Verification</h1>";

// Check if the admin test page class exists
if (class_exists('SLList_Store_Update_Test')) {
    echo "<p>✅ <strong>SLList_Store_Update_Test class is loaded!</strong></p>";
    
    // Check if admin menu action is hooked
    global $wp_filter;
    if (isset($wp_filter['admin_menu'])) {
        $admin_menu_callbacks = $wp_filter['admin_menu']->callbacks;
        $found_callback = false;
        foreach ($admin_menu_callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && 
                    is_object($callback['function'][0]) && 
                    get_class($callback['function'][0]) === 'SLList_Store_Update_Test') {
                    $found_callback = true;
                    break 2;
                }
            }
        }
        
        if ($found_callback) {
            echo "<p>✅ <strong>Admin menu callback is properly hooked!</strong></p>";
        } else {
            echo "<p>❌ <strong>Admin menu callback not found in hooks.</strong></p>";
        }
    }
    
    // Provide link to admin page
    $admin_url = admin_url('tools.php?page=sllist-store-update-test');
    echo "<p><strong>Admin Test Page URL:</strong><br>";
    echo "<a href='{$admin_url}' target='_blank'>{$admin_url}</a></p>";
    
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li>Go to your WordPress Admin</li>";
    echo "<li>Navigate to <strong>Tools</strong> in the left menu</li>";
    echo "<li>Look for <strong>Store Update Test</strong> in the submenu</li>";
    echo "<li>Click on it to access the test page</li>";
    echo "</ol>";
    
} else {
    echo "<p>❌ <strong>SLList_Store_Update_Test class is NOT loaded.</strong></p>";
    
    echo "<h2>Debugging Information:</h2>";
    
    // Check if file exists
    $plugin_path = plugin_dir_path(dirname(dirname(__FILE__)));
    $test_file = $plugin_path . 'includes/admin/admin-test-page.php';
    
    echo "<p><strong>Plugin Path:</strong> $plugin_path</p>";
    echo "<p><strong>Test File Path:</strong> $test_file</p>";
    echo "<p><strong>File Exists:</strong> " . (file_exists($test_file) ? '✅ Yes' : '❌ No') . "</p>";
    
    // Check conditions
    echo "<p><strong>WP_DEBUG:</strong> " . (defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled') . "</p>";
    echo "<p><strong>is_admin():</strong> " . (is_admin() ? '✅ True' : '❌ False') . "</p>";
    
    echo "<h2>Troubleshooting:</h2>";
    echo "<ul>";
    echo "<li>Make sure the file exists at: <code>$test_file</code></li>";
    echo "<li>Enable WP_DEBUG in wp-config.php or access from admin area</li>";
    echo "<li>Check if the plugin is properly activated</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><em>Generated at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
