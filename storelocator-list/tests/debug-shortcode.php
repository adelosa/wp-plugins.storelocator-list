<?php
/**
 * Debug Shortcode Registration
 * 
 * This script tests if the shortcode is properly registered.
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-shortcode.php
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
    die('<h1>WordPress Not Found</h1><p>Could not load WordPress. Please access this file through your WordPress installation.</p><p>Try: <code>http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-shortcode.php</code></p>');
}

echo "<h1>Shortcode Debug Information</h1>";

// Check if shortcode is registered
global $shortcode_tags;

echo "<h2>Registered Shortcodes</h2>";
if (isset($shortcode_tags['sllist'])) {
    echo "<p>✅ <strong>sllist shortcode is registered</strong></p>";
    echo "<p><strong>Callback:</strong> " . print_r($shortcode_tags['sllist'], true) . "</p>";
} else {
    echo "<p>❌ <strong>sllist shortcode is NOT registered</strong></p>";
}

echo "<h2>All Registered Shortcodes</h2>";
echo "<p>Total shortcodes: " . count($shortcode_tags) . "</p>";
echo "<details>";
echo "<summary>Click to see all shortcodes</summary>";
echo "<pre>";
foreach ($shortcode_tags as $tag => $callback) {
    echo "$tag => " . (is_array($callback) ? implode('::', $callback) : $callback) . "\n";
}
echo "</pre>";
echo "</details>";

// Test plugin classes
echo "<h2>Plugin Classes</h2>";
$classes_to_check = [
    'StoreLocatorList\\SLList_Plugin',
    'StoreLocatorList\\Core\\SLList_Shortcodes',
    'StoreLocatorList\\Core\\SLList_Query_Helper',
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "<p>✅ <strong>Class exists:</strong> $class</p>";
    } else {
        echo "<p>❌ <strong>Class missing:</strong> $class</p>";
    }
}

// Test shortcode execution
echo "<h2>Shortcode Test</h2>";
$test_shortcode = '[sllist]';
echo "<p><strong>Testing shortcode:</strong> <code>$test_shortcode</code></p>";

$output = do_shortcode($test_shortcode);
echo "<p><strong>Shortcode output length:</strong> " . strlen($output) . " characters</p>";

if (strlen($output) > 0 && strpos($output, '[sllist]') === false) {
    echo "<p>✅ <strong>Shortcode is working</strong> - generated content</p>";
    echo "<details>";
    echo "<summary>Click to see output</summary>";
    echo "<div style='background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow: auto;'>";
    echo htmlspecialchars($output);
    echo "</div>";
    echo "</details>";
} else if (strpos($output, '[sllist]') !== false) {
    echo "<p>❌ <strong>Shortcode not processed</strong> - returned unchanged</p>";
} else {
    echo "<p>⚠️ <strong>Shortcode processed but no output</strong> - check if stores exist</p>";
}

// Check for stores
echo "<h2>Store Data</h2>";
$store_count = wp_count_posts('wpsl_stores');
echo "<p><strong>Total stores:</strong> " . $store_count->publish . " published, " . $store_count->pending . " pending</p>";

if ($store_count->publish == 0 && $store_count->pending == 0) {
    echo "<p>ℹ️ No stores found - this might explain why the shortcode produces no output</p>";
    echo "<p><a href='" . home_url('/create-test-stores.php') . "'>Create Test Stores</a></p>";
}

// Check for plugin activation
echo "<h2>Plugin Status</h2>";
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
}

?>
