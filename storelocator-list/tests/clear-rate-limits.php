<?php
/**
 * Clear Rate Limiting Cache
 * 
 * Run this script to clear all rate limiting data.
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/clear-rate-limits.php
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
    die('<h1>WordPress Not Found</h1><p>Could not load WordPress. Please access this file through your WordPress installation.</p><p>Try: <code>http://localhost:8080/wp-content/plugins/storelocator-list/tests/clear-rate-limits.php</code></p>');
}

echo "<h1>Clearing Rate Limiting Cache</h1>";

// Get current IP
$ip = $_SERVER['REMOTE_ADDR'];
echo "<p><strong>Your IP:</strong> $ip</p>";

// Clear rate limiting transients
$actions = ['store_search', 'access_request', 'general'];
$cleared = 0;

foreach ($actions as $action) {
    $transient_key = 'sllist_rate_limit_' . md5($action . $ip);
    if (delete_transient($transient_key)) {
        echo "<p>✅ Cleared rate limit for: <strong>$action</strong></p>";
        $cleared++;
    } else {
        echo "<p>ℹ️ No rate limit data found for: <strong>$action</strong></p>";
    }
}

echo "<hr>";
echo "<p><strong>Summary:</strong> Cleared $cleared rate limiting entries</p>";
echo "<p>You can now test the store search functionality without rate limiting restrictions.</p>";

echo "<h2>Test Links</h2>";
echo "<ul>";
echo "<li><a href='" . home_url('/?sllist_page=store_manager') . "' target='_blank'>Store Manager Page</a></li>";
echo "<li><a href='" . home_url('/create-test-stores.php') . "' target='_blank'>Create Test Stores</a></li>";
echo "</ul>";

?>
