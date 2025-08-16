<?php
/**
 * Test Script and Asset Loading
 * 
 * This script helps verify that JavaScript and CSS files are loading properly
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-asset-loading.php
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Asset Loading</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #46b450; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3232; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>Test Asset Loading</h1>

<?php

echo '<div class="test-section">';
echo '<h2>🔍 Plugin Asset Files</h2>';

// Get plugin instance
$plugin = \StoreLocatorList\SLList_Plugin::get_instance();
$plugin_url = $plugin->get_plugin_url();
$plugin_path = plugin_dir_path(dirname(dirname(__FILE__)));

echo '<p><strong>Plugin URL:</strong> ' . esc_html($plugin_url) . '</p>';
echo '<p><strong>Plugin Path:</strong> ' . esc_html($plugin_path) . '</p>';

$assets_to_check = [
    'CSS Files' => [
        'sllist-styles.css' => 'assets/css/sllist-styles.css',
        'store-update.css' => 'assets/css/store-update.css'
    ],
    'JavaScript Files' => [
        'sllist-scripts.js' => 'assets/js/sllist-scripts.js',
        'store-search.js' => 'assets/js/store-search.js',
        'store-update.js' => 'assets/js/store-update.js'
    ]
];

foreach ($assets_to_check as $category => $files) {
    echo '<h3>' . esc_html($category) . '</h3>';
    echo '<table>';
    echo '<tr><th>File</th><th>Path</th><th>URL</th><th>Status</th><th>Test</th></tr>';
    
    foreach ($files as $name => $relative_path) {
        $full_path = $plugin_path . $relative_path;
        $full_url = $plugin_url . $relative_path;
        $exists = file_exists($full_path);
        
        echo '<tr>';
        echo '<td>' . esc_html($name) . '</td>';
        echo '<td><code>' . esc_html($relative_path) . '</code></td>';
        echo '<td><code>' . esc_html($full_url) . '</code></td>';
        echo '<td>' . ($exists ? '<span style="color: green;">✅ Exists</span>' : '<span style="color: red;">❌ Missing</span>') . '</td>';
        echo '<td><a href="' . esc_attr($full_url) . '" target="_blank" class="button" style="font-size: 12px; padding: 5px 10px;">Test URL</a></td>';
        echo '</tr>';
    }
    
    echo '</table>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Test AJAX Endpoints</h2>';

$ajax_endpoints = [
    'Store Search' => 'sllist_search_stores',
    'Request Store Access' => 'sllist_request_store_access',
    'Authenticate Store Access' => 'sllist_authenticate_store_access',
    'Update Store Details' => 'sllist_update_store_details'
];

echo '<p>These AJAX endpoints should be available:</p>';
echo '<table>';
echo '<tr><th>Function</th><th>Action</th><th>Test URL</th></tr>';

foreach ($ajax_endpoints as $name => $action) {
    $test_url = admin_url('admin-ajax.php') . '?action=' . $action;
    echo '<tr>';
    echo '<td>' . esc_html($name) . '</td>';
    echo '<td><code>' . esc_html($action) . '</code></td>';
    echo '<td><a href="' . esc_attr($test_url) . '" target="_blank">' . esc_html($test_url) . '</a></td>';
    echo '</tr>';
}

echo '</table>';
echo '<p><em>Note: These URLs will return errors when called directly, but they should not return 404.</em></p>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 Script Enqueuing Test</h2>';

// Test script enqueuing by simulating the page conditions
echo '<h3>Store Manager Page Scripts:</h3>';

// Simulate store manager page
$_GET['sllist_page'] = 'store_manager';
$store_search = new \StoreLocatorList\PublicPages\SLList_Store_Search();

ob_start();
$store_search->enqueue_scripts();
$enqueue_output = ob_get_clean();

echo '<p><strong>Simulated script enqueuing for store manager page...</strong></p>';

// Check if scripts were enqueued
global $wp_scripts, $wp_styles;

$enqueued_scripts = [];
$enqueued_styles = [];

if ($wp_scripts && is_object($wp_scripts)) {
    foreach ($wp_scripts->queue as $handle) {
        if (strpos($handle, 'sllist') !== false) {
            $enqueued_scripts[] = $handle;
        }
    }
}

if ($wp_styles && is_object($wp_styles)) {
    foreach ($wp_styles->queue as $handle) {
        if (strpos($handle, 'sllist') !== false) {
            $enqueued_styles[] = $handle;
        }
    }
}

echo '<p><strong>Enqueued Scripts:</strong> ' . (empty($enqueued_scripts) ? 'None' : implode(', ', $enqueued_scripts)) . '</p>';
echo '<p><strong>Enqueued Styles:</strong> ' . (empty($enqueued_styles) ? 'None' : implode(', ', $enqueued_styles)) . '</p>';

// Clean up
unset($_GET['sllist_page']);

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🌐 Live Page Tests</h2>';

echo '<p>Test these pages to verify everything is working:</p>';
echo '<ul>';
echo '<li><strong>Store Manager:</strong> <a href="' . home_url('/store-manager/') . '" target="_blank">' . home_url('/store-manager/') . '</a></li>';
echo '<li><strong>Store Update:</strong> <a href="' . home_url('/update-store/') . '" target="_blank">' . home_url('/update-store/') . '</a></li>';
echo '<li><strong>Store Manager (Fallback):</strong> <a href="' . home_url('/?sllist_page=store_manager') . '" target="_blank">' . home_url('/?sllist_page=store_manager') . '</a></li>';
echo '<li><strong>Store Update (Fallback):</strong> <a href="' . home_url('/?sllist_page=store_update') . '" target="_blank">' . home_url('/?sllist_page=store_update') . '</a></li>';
echo '</ul>';

echo '<h3>What to Check:</h3>';
echo '<ul>';
echo '<li>✅ Page loads without errors</li>';
echo '<li>✅ CSS styles are applied</li>';
echo '<li>✅ JavaScript console shows no 404 errors</li>';
echo '<li>✅ AJAX functionality works (if testing with forms)</li>';
echo '</ul>';

echo '</div>';

?>

<script>
// Test if jQuery is available
if (typeof jQuery !== 'undefined') {
    console.log('✅ jQuery is available');
} else {
    console.log('❌ jQuery is not available');
}

// Add some debug output
console.log('Asset Loading Test Page Loaded');
console.log('Plugin URL from PHP:', '<?php echo esc_js($plugin_url); ?>');
</script>

</body>
</html>
