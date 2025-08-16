<?php
/**
 * Debug script for Store Search functionality
 * 
 * Place this file in your WordPress plugin directory and access it directly
 * to test if the store search functionality is working.
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-store-search.php
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
    die('<h1>WordPress Not Found</h1><p>Could not load WordPress. Please access this file through your WordPress installation.</p><p>Try: <code>http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-store-search.php</code></p>');
}

echo "<h1>Store Search Debug Information</h1>";

// Check if plugin is active
$active_plugins = get_option('active_plugins');
$plugin_active = false;
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'storelocator-list') !== false) {
        $plugin_active = true;
        echo "<p>✅ <strong>Plugin is active:</strong> $plugin</p>";
        break;
    }
}

if (!$plugin_active) {
    echo "<p>❌ <strong>Plugin is NOT active</strong></p>";
    exit;
}

// Check if classes exist
echo "<h2>Class Availability</h2>";
$classes_to_check = [
    'StoreLocatorList\\PublicPages\\SLList_Store_Search',
    'StoreLocatorList\\Core\\SLList_Security',
    'StoreLocatorList\\Core\\SLList_Query_Helper'
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "<p>✅ <strong>Class exists:</strong> $class</p>";
    } else {
        echo "<p>❌ <strong>Class missing:</strong> $class</p>";
    }
}

// Test query var
echo "<h2>Query Variable Test</h2>";
$_GET['sllist_page'] = 'store_manager';
$wp_query = new WP_Query();
$wp_query->parse_query();

if (get_query_var('sllist_page') === 'store_manager') {
    echo "<p>✅ <strong>Query var working:</strong> sllist_page = " . get_query_var('sllist_page') . "</p>";
} else {
    echo "<p>❌ <strong>Query var not working</strong></p>";
    echo "<p>Current query vars: " . print_r(get_query_var('sllist_page'), true) . "</p>";
}

// Test if store search class can be instantiated
echo "<h2>Store Search Class Test</h2>";
try {
    if (class_exists('StoreLocatorList\\PublicPages\\SLList_Store_Search')) {
        $store_search = new StoreLocatorList\PublicPages\SLList_Store_Search();
        echo "<p>✅ <strong>Store Search class instantiated successfully</strong></p>";
        
        // Test the URL method
        if (method_exists($store_search, 'get_store_manager_url')) {
            echo "<p>✅ <strong>get_store_manager_url method exists</strong></p>";
            echo "<p><strong>Pretty URL:</strong> " . $store_search::get_store_manager_url(true) . "</p>";
            echo "<p><strong>Query URL:</strong> " . $store_search::get_store_manager_url(false) . "</p>";
        } else {
            echo "<p>❌ <strong>get_store_manager_url method missing</strong></p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ <strong>Error instantiating Store Search class:</strong> " . $e->getMessage() . "</p>";
}

// Check if wpsl_stores post type exists
echo "<h2>Store Data Test</h2>";
$post_types = get_post_types();
if (in_array('wpsl_stores', $post_types)) {
    echo "<p>✅ <strong>wpsl_stores post type exists</strong></p>";
    
    // Count stores
    $store_count = wp_count_posts('wpsl_stores');
    echo "<p><strong>Total stores:</strong> " . $store_count->publish . " published</p>";
} else {
    echo "<p>❌ <strong>wpsl_stores post type not found</strong></p>";
    echo "<p><strong>Available post types:</strong> " . implode(', ', $post_types) . "</p>";
}

// Check rewrite rules
echo "<h2>Rewrite Rules Test</h2>";
$rewrite_rules = get_option('rewrite_rules');
$store_rule_found = false;
foreach ($rewrite_rules as $pattern => $rewrite) {
    if (strpos($pattern, 'store-manager') !== false || strpos($rewrite, 'sllist_page') !== false) {
        echo "<p>✅ <strong>Store manager rewrite rule found:</strong></p>";
        echo "<p><code>$pattern => $rewrite</code></p>";
        $store_rule_found = true;
    }
}

if (!$store_rule_found) {
    echo "<p>❌ <strong>No store manager rewrite rules found</strong></p>";
    echo "<p><em>Try going to Settings → Permalinks and click 'Save Changes' to flush rewrite rules.</em></p>";
}

echo "<h2>Manual Page Test</h2>";
echo "<p>Testing the store manager page directly...</p>";

// Simulate the page request
global $wp_query;
$wp_query->set('sllist_page', 'store_manager');
set_query_var('sllist_page', 'store_manager');

if (class_exists('StoreLocatorList\\PublicPages\\SLList_Store_Search')) {
    $store_search = new StoreLocatorList\PublicPages\SLList_Store_Search();
    
    echo "<h3>Store Manager Page Output:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
    
    // Capture the output
    ob_start();
    try {
        // Don't call get_header/get_footer in debug mode
        echo '<div class="sllist-store-manager-page">';
        echo '<div class="container">';
        
        // Call the render methods directly
        if (method_exists($store_search, 'render_search_form')) {
            $reflection = new ReflectionClass($store_search);
            $method = $reflection->getMethod('render_search_form');
            $method->setAccessible(true);
            $method->invoke($store_search);
        }
        
        echo '</div>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo "❌ <strong>Error rendering page:</strong> " . $e->getMessage();
    }
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "✅ <strong>Page content generated successfully</strong>";
        echo $output;
    } else {
        echo "❌ <strong>No page content generated</strong>";
    }
    
    echo "</div>";
}

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>If classes are missing, check that all plugin files are uploaded correctly</li>";
echo "<li>If query vars aren't working, try flushing permalinks (Settings → Permalinks → Save)</li>";
echo "<li>If stores aren't found, check that WP Store Locator plugin is active and has stores</li>";
echo "<li>If rewrite rules are missing, deactivate and reactivate the plugin</li>";
echo "</ul>";

?>
