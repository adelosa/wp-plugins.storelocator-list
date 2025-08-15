<?php
/**
 * Clear Rate Limiting Cache
 * 
 * Run this script to clear all rate limiting data
 */

// Load WordPress
require_once('wp-config.php');
require_once('wp-load.php');

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
