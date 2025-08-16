<?php
/**
 * Debug Rewrite Rules
 * 
 * This script helps debug what rewrite rules are actually registered
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-rewrite-rules.php
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
    <title>Debug Rewrite Rules</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #46b450; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3232; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>

<h1>Debug Rewrite Rules</h1>

<?php

echo '<div class="test-section">';
echo '<h2>🔍 Current WordPress Configuration</h2>';

// Check permalink structure
$permalink_structure = get_option('permalink_structure');
echo '<p><strong>Permalink Structure:</strong> ' . (empty($permalink_structure) ? 'Plain (THIS IS THE PROBLEM!)' : esc_html($permalink_structure)) . '</p>';

if (empty($permalink_structure)) {
    echo '<div class="error">';
    echo '<h3>❌ CRITICAL ISSUE: Permalinks are set to "Plain"</h3>';
    echo '<p>Pretty URLs cannot work when permalinks are set to "Plain". You must enable pretty permalinks first.</p>';
    echo '<p><a href="' . admin_url('options-permalink.php') . '" class="button">Go to Permalink Settings</a></p>';
    echo '</div>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 All Registered Rewrite Rules</h2>';

$rewrite_rules = get_option('rewrite_rules', []);

if (empty($rewrite_rules)) {
    echo '<div class="error">❌ No rewrite rules found! This indicates a serious problem.</div>';
} else {
    echo '<p><strong>Total rules:</strong> ' . count($rewrite_rules) . '</p>';
    
    // Look for our specific rules
    $found_store_rules = [];
    $other_rules = [];
    
    foreach ($rewrite_rules as $pattern => $replacement) {
        if (strpos($pattern, 'store-manager') !== false || strpos($pattern, 'update-store') !== false) {
            $found_store_rules[$pattern] = $replacement;
        } else {
            $other_rules[$pattern] = $replacement;
        }
    }
    
    if (!empty($found_store_rules)) {
        echo '<div class="success">';
        echo '<h3>✅ Found Store-Related Rules (' . count($found_store_rules) . ')</h3>';
        echo '<table>';
        echo '<tr><th>Pattern</th><th>Replacement</th></tr>';
        foreach ($found_store_rules as $pattern => $replacement) {
            echo '<tr>';
            echo '<td><code>' . esc_html($pattern) . '</code></td>';
            echo '<td><code>' . esc_html($replacement) . '</code></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<h3>❌ No Store-Related Rules Found!</h3>';
        echo '<p>Our rewrite rules are not registered. This could be because:</p>';
        echo '<ul>';
        echo '<li>The plugin is not loaded properly</li>';
        echo '<li>The init action is not firing</li>';
        echo '<li>Rewrite rules need to be flushed</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    // Show first 10 other rules for reference
    echo '<h3>Sample of Other Rules (first 10)</h3>';
    echo '<table>';
    echo '<tr><th>Pattern</th><th>Replacement</th></tr>';
    $count = 0;
    foreach ($other_rules as $pattern => $replacement) {
        if ($count >= 10) break;
        echo '<tr>';
        echo '<td><code>' . esc_html($pattern) . '</code></td>';
        echo '<td><code>' . esc_html($replacement) . '</code></td>';
        echo '</tr>';
        $count++;
    }
    echo '</table>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Manual Rule Testing</h2>';

// Test our patterns manually
$test_urls = [
    'store-manager',
    'store-manager/',
    'update-store',
    'update-store/'
];

echo '<p>Testing if our patterns would match manually:</p>';
echo '<table>';
echo '<tr><th>Test URL</th><th>Pattern: ^store-manager/?$</th><th>Pattern: ^update-store/?$</th></tr>';

foreach ($test_urls as $test_url) {
    echo '<tr>';
    echo '<td><code>' . esc_html($test_url) . '</code></td>';
    echo '<td>' . (preg_match('#^store-manager/?$#', $test_url) ? '✅ Match' : '❌ No match') . '</td>';
    echo '<td>' . (preg_match('#^update-store/?$#', $test_url) ? '✅ Match' : '❌ No match') . '</td>';
    echo '</tr>';
}

echo '</table>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 Actions to Fix</h2>';

if (empty($permalink_structure)) {
    echo '<div class="error">';
    echo '<h3>STEP 1: Enable Pretty Permalinks</h3>';
    echo '<ol>';
    echo '<li>Go to <a href="' . admin_url('options-permalink.php') . '" target="_blank">Settings → Permalinks</a></li>';
    echo '<li>Select "Post name" or any option other than "Plain"</li>';
    echo '<li>Click "Save Changes"</li>';
    echo '</ol>';
    echo '</div>';
}

echo '<div class="info">';
echo '<h3>STEP 2: Flush Rewrite Rules</h3>';
echo '<p>After enabling pretty permalinks, flush the rewrite rules:</p>';
echo '<form method="post" style="display: inline;">';
echo '<input type="hidden" name="action" value="flush_rewrite_rules">';
echo '<input type="submit" class="button" value="Flush Rewrite Rules Now">';
echo '</form>';

if (isset($_POST['action']) && $_POST['action'] === 'flush_rewrite_rules') {
    flush_rewrite_rules(true);
    echo '<div class="success">✅ Rewrite rules flushed! <a href="?" class="button">Refresh Page</a></div>';
}

echo '</div>';

echo '<div class="warning">';
echo '<h3>STEP 3: Re-test URLs</h3>';
echo '<p>After completing steps 1-2, test these URLs again:</p>';
echo '<ul>';
echo '<li><a href="' . home_url('/store-manager/') . '" target="_blank">' . home_url('/store-manager/') . '</a></li>';
echo '<li><a href="' . home_url('/update-store/') . '" target="_blank">' . home_url('/update-store/') . '</a></li>';
echo '</ul>';
echo '</div>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>📋 Debug Information</h2>';

echo '<h3>WordPress Environment</h3>';
echo '<ul>';
echo '<li><strong>Home URL:</strong> ' . home_url() . '</li>';
echo '<li><strong>Site URL:</strong> ' . site_url() . '</li>';
echo '<li><strong>WP_REWRITE Class:</strong> ' . (class_exists('WP_Rewrite') ? 'Available' : 'Missing') . '</li>';
echo '<li><strong>mod_rewrite:</strong> ' . (got_mod_rewrite() ? 'Enabled' : 'Disabled/Unknown') . '</li>';
echo '</ul>';

// Check if our classes are loaded
echo '<h3>Plugin Classes</h3>';
echo '<ul>';
$classes_to_check = [
    'StoreLocatorList\PublicPages\SLList_Store_Search',
    'StoreLocatorList\PublicPages\SLList_Store_Update',
    'StoreLocatorList\SLList_Plugin'
];

foreach ($classes_to_check as $class) {
    echo '<li><strong>' . esc_html($class) . ':</strong> ' . (class_exists($class) ? '✅ Loaded' : '❌ Missing') . '</li>';
}
echo '</ul>';

echo '</div>';

?>

</body>
</html>
