<?php
/**
 * Fix Rewrite Rules
 * 
 * This script manually registers our rewrite rules and flushes them
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/fix-rewrite-rules.php
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
    <title>Fix Rewrite Rules</title>
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
    </style>
</head>
<body>

<h1>Fix Rewrite Rules</h1>

<?php

echo '<div class="test-section">';
echo '<h2>🔧 Manual Rewrite Rule Registration</h2>';

if (isset($_POST['action']) && $_POST['action'] === 'fix_rewrite_rules') {
    echo '<div class="info"><h3>🚀 Running Fix Process...</h3></div>';
    
    // Step 1: Add query vars manually
    echo '<p><strong>Step 1:</strong> Adding query vars...</p>';
    global $wp;
    if (!in_array('sllist_page', $wp->public_query_vars)) {
        $wp->add_query_var('sllist_page');
        echo '<div class="success">✅ Added sllist_page to query vars</div>';
    } else {
        echo '<div class="warning">⚠️ sllist_page already in query vars</div>';
    }
    
    // Step 2: Add rewrite rules manually
    echo '<p><strong>Step 2:</strong> Adding rewrite rules...</p>';
    
    // Add rules for both pages
    add_rewrite_rule(
        '^store-manager/?$',
        'index.php?sllist_page=store_manager',
        'top'
    );
    
    add_rewrite_rule(
        '^update-store/?$',
        'index.php?sllist_page=update_store',
        'top'
    );
    
    echo '<div class="success">✅ Added rewrite rules for store-manager and update-store</div>';
    
    // Step 3: Flush rewrite rules
    echo '<p><strong>Step 3:</strong> Flushing rewrite rules...</p>';
    flush_rewrite_rules(true);
    echo '<div class="success">✅ Rewrite rules flushed</div>';
    
    // Step 4: Set option to prevent repeated flushing
    update_option('sllist_rewrite_rules_flushed', time());
    echo '<div class="success">✅ Set timestamp to prevent repeated flushing</div>';
    
    echo '<div class="success">';
    echo '<h3>🎉 Fix Complete!</h3>';
    echo '<p>Your rewrite rules should now be working. Test the URLs below:</p>';
    echo '<ul>';
    echo '<li><a href="' . home_url('/store-manager/') . '" target="_blank">' . home_url('/store-manager/') . '</a></li>';
    echo '<li><a href="' . home_url('/update-store/') . '" target="_blank">' . home_url('/update-store/') . '</a></li>';
    echo '</ul>';
    echo '</div>';
    
} else {
    echo '<p>This will manually register the rewrite rules and flush them.</p>';
    echo '<form method="post">';
    echo '<input type="hidden" name="action" value="fix_rewrite_rules">';
    echo '<input type="submit" class="button" value="Fix Rewrite Rules Now" onclick="return confirm(\'This will flush all rewrite rules. Continue?\');">';
    echo '</form>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔍 Current Status Check</h2>';

// Check current rules
$rewrite_rules = get_option('rewrite_rules', []);
$found_store_rules = [];

foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'store-manager') !== false || strpos($pattern, 'update-store') !== false) {
        $found_store_rules[$pattern] = $replacement;
    }
}

if (!empty($found_store_rules)) {
    echo '<div class="success">';
    echo '<h3>✅ Store Rules Found (' . count($found_store_rules) . ')</h3>';
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
    echo '<h3>❌ No Store Rules Found</h3>';
    echo '<p>The rewrite rules are still not registered. Use the button above to fix this.</p>';
    echo '</div>';
}

// Check query vars
global $wp;
echo '<h3>Query Variables</h3>';
echo '<p><strong>sllist_page in public_query_vars:</strong> ' . (in_array('sllist_page', $wp->public_query_vars) ? '✅ Yes' : '❌ No') . '</p>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Test URLs</h2>';

echo '<p>After running the fix, test these URLs:</p>';
echo '<ul>';
echo '<li><a href="' . home_url('/store-manager/') . '" target="_blank">Store Manager (Pretty URL)</a></li>';
echo '<li><a href="' . home_url('/?sllist_page=store_manager') . '" target="_blank">Store Manager (Query Parameter)</a></li>';
echo '<li><a href="' . home_url('/update-store/') . '" target="_blank">Update Store (Pretty URL)</a></li>';
echo '<li><a href="' . home_url('/?sllist_page=update_store') . '" target="_blank">Update Store (Query Parameter)</a></li>';
echo '</ul>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 Alternative: Plugin Reactivation</h2>';

echo '<div class="warning">';
echo '<h3>Alternative Solution</h3>';
echo '<p>If the manual fix above doesn\'t work, you can also try:</p>';
echo '<ol>';
echo '<li>Go to <a href="' . admin_url('plugins.php') . '" target="_blank">Plugins</a></li>';
echo '<li>Deactivate the "Store Locator List" plugin</li>';
echo '<li>Reactivate the "Store Locator List" plugin</li>';
echo '</ol>';
echo '<p>This will trigger the plugin\'s activation hook which should register the rewrite rules.</p>';
echo '</div>';

echo '</div>';

?>

</body>
</html>
