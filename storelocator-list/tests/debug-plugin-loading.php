<?php
/**
 * Comprehensive Plugin Debug
 * 
 * This script checks plugin loading, hook registration, and query variable issues
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-plugin-loading.php
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Plugin Debug</title>
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
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; font-size: 12px; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>

<h1>Comprehensive Plugin Debug</h1>

<?php

// Process manual fixes
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'manual_query_var':
            echo '<div class="info"><h3>🔧 Manually Adding Query Variable</h3></div>';
            global $wp;
            $wp->add_query_var('sllist_page');
            echo '<div class="success">✅ Added sllist_page to WordPress query vars</div>';
            break;
            
        case 'manual_rewrite_rules':
            echo '<div class="info"><h3>🔧 Manually Adding Rewrite Rules</h3></div>';
            add_rewrite_rule('^store-manager/?$', 'index.php?sllist_page=store_manager', 'top');
            add_rewrite_rule('^update-store/?$', 'index.php?sllist_page=store_update', 'top');
            flush_rewrite_rules(true);
            echo '<div class="success">✅ Added rewrite rules and flushed</div>';
            break;
            
        case 'force_flush':
            echo '<div class="info"><h3>🔧 Force Flushing Rewrite Rules</h3></div>';
            flush_rewrite_rules(true);
            echo '<div class="success">✅ Rewrite rules flushed with hard flush</div>';
            break;
    }
}

echo '<div class="test-section">';
echo '<h2>🔍 Plugin Loading Status</h2>';

// Check if our main plugin class exists
$main_class = 'StoreLocatorList\\SLList_Plugin';
echo '<p><strong>Main Plugin Class:</strong> ' . (class_exists($main_class) ? '✅ Loaded' : '❌ Missing') . '</p>';

if (class_exists($main_class)) {
    $plugin_instance = call_user_func(array($main_class, 'get_instance'));
    echo '<p><strong>Plugin Instance:</strong> ' . (is_object($plugin_instance) ? '✅ Created' : '❌ Failed') . '</p>';
}

// Check if plugin is in active plugins list
$active_plugins = get_option('active_plugins', []);
$plugin_file = 'storelocator-list/storelocator-list.php';
$is_active = in_array($plugin_file, $active_plugins);
echo '<p><strong>Plugin Active Status:</strong> ' . ($is_active ? '✅ Active' : '❌ Inactive') . '</p>';

if (!$is_active) {
    echo '<div class="error">';
    echo '<h3>❌ Plugin Not Active!</h3>';
    echo '<p>The plugin is not active in WordPress. This could be why hooks aren\'t registering.</p>';
    echo '<p><a href="' . admin_url('plugins.php') . '" target="_blank">Go to Plugins Page</a></p>';
    echo '</div>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔗 Hook Registration Check</h2>';

global $wp_filter;

// Check if our hooks are registered
$hooks_to_check = [
    'query_vars' => 'SLList_Plugin->add_query_vars',
    'init' => 'SLList_Plugin->init',
    'parse_request' => 'SLList_Store_*->parse_request',
    'template_redirect' => 'SLList_Store_*->handle_*_page',
    'template_include' => 'SLList_Store_*->template_include'
];

echo '<table>';
echo '<tr><th>Hook</th><th>Expected Function</th><th>Found</th><th>Details</th></tr>';

foreach ($hooks_to_check as $hook => $expected) {
    $found = false;
    $details = [];
    
    if (isset($wp_filter[$hook])) {
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback_id => $callback) {
                if (is_array($callback['function'])) {
                    if (is_object($callback['function'][0])) {
                        $class_name = get_class($callback['function'][0]);
                        $method_name = $callback['function'][1];
                        
                        if (strpos($class_name, 'SLList') !== false) {
                            $found = true;
                            $details[] = "{$class_name}->{$method_name} (priority {$priority})";
                        }
                    }
                } elseif (is_string($callback['function']) && strpos($callback['function'], 'SLList') !== false) {
                    $found = true;
                    $details[] = "{$callback['function']} (priority {$priority})";
                }
            }
        }
    }
    
    echo '<tr>';
    echo '<td><code>' . esc_html($hook) . '</code></td>';
    echo '<td>' . esc_html($expected) . '</td>';
    echo '<td>' . ($found ? '✅ Found' : '❌ Missing') . '</td>';
    echo '<td>' . (empty($details) ? 'None' : implode('<br>', array_map('esc_html', $details))) . '</td>';
    echo '</tr>';
}

echo '</table>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔍 Query Variables Status</h2>';

global $wp;

echo '<h3>WordPress Query Variables</h3>';
echo '<p><strong>Total public query vars:</strong> ' . count($wp->public_query_vars) . '</p>';
echo '<p><strong>sllist_page in public_query_vars:</strong> ' . (in_array('sllist_page', $wp->public_query_vars) ? '✅ Yes' : '❌ No') . '</p>';

if (!in_array('sllist_page', $wp->public_query_vars)) {
    echo '<div class="error">';
    echo '<h4>❌ Query Variable Not Registered</h4>';
    echo '<p>The sllist_page query variable is not in WordPress\'s public query vars list.</p>';
    echo '<form method="post" style="display: inline;">';
    echo '<input type="hidden" name="action" value="manual_query_var">';
    echo '<input type="submit" class="button" value="Add Query Variable Manually">';
    echo '</form>';
    echo '</div>';
}

// Show first 20 query vars for reference
echo '<h4>Current Query Variables (first 20)</h4>';
echo '<div class="code">';
$query_vars = array_slice($wp->public_query_vars, 0, 20);
foreach ($query_vars as $var) {
    echo esc_html($var) . '<br>';
}
if (count($wp->public_query_vars) > 20) {
    echo '... and ' . (count($wp->public_query_vars) - 20) . ' more';
}
echo '</div>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔍 Rewrite Rules Status</h2>';

$rewrite_rules = get_option('rewrite_rules', []);
echo '<p><strong>Total rewrite rules:</strong> ' . count($rewrite_rules) . '</p>';

// Look for our rules
$our_rules = [];
foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'store-manager') !== false || strpos($pattern, 'update-store') !== false) {
        $our_rules[$pattern] = $replacement;
    }
}

if (!empty($our_rules)) {
    echo '<div class="success">';
    echo '<h3>✅ Our Rewrite Rules Found</h3>';
    echo '<table>';
    echo '<tr><th>Pattern</th><th>Replacement</th></tr>';
    foreach ($our_rules as $pattern => $replacement) {
        echo '<tr>';
        echo '<td><code>' . esc_html($pattern) . '</code></td>';
        echo '<td><code>' . esc_html($replacement) . '</code></td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<h3>❌ Our Rewrite Rules Missing</h3>';
    echo '<p>The store-manager and update-store rewrite rules are not found.</p>';
    echo '<form method="post" style="display: inline;">';
    echo '<input type="hidden" name="action" value="manual_rewrite_rules">';
    echo '<input type="submit" class="button" value="Add Rewrite Rules Manually">';
    echo '</form>';
    echo '</div>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Live URL Test</h2>';

// Test our URL parsing directly
echo '<h3>Direct URL Parse Test</h3>';

$test_wp = new WP();

// Manually set the query var first
global $wp;
if (!in_array('sllist_page', $wp->public_query_vars)) {
    $wp->add_query_var('sllist_page');
    echo '<p><strong>Note:</strong> Added sllist_page to query vars for this test</p>';
}

// Now test parsing
$test_wp->parse_request('update-store/');

echo '<h4>Parse Result for "update-store/"</h4>';
echo '<pre>';
echo 'Query vars: ' . print_r($test_wp->query_vars, true);
echo 'Matched rule: ' . $test_wp->matched_rule . "\n";
echo 'Matched query: ' . $test_wp->matched_query . "\n";
echo '</pre>';

if (isset($test_wp->query_vars['sllist_page'])) {
    echo '<div class="success">✅ Test successful: sllist_page = ' . esc_html($test_wp->query_vars['sllist_page']) . '</div>';
} else {
    echo '<div class="error">❌ Test failed: sllist_page not found in query vars</div>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 Manual Fix Actions</h2>';

echo '<p>If the automatic plugin loading isn\'t working, try these manual fixes:</p>';

echo '<form method="post" style="display: inline;">';
echo '<input type="hidden" name="action" value="force_flush">';
echo '<input type="submit" class="button" value="Force Flush Rewrite Rules">';
echo '</form>';

echo '<div class="warning">';
echo '<h3>Alternative: Direct WordPress Admin</h3>';
echo '<ol>';
echo '<li><a href="' . admin_url('plugins.php') . '" target="_blank">Check if plugin is active</a></li>';
echo '<li><a href="' . admin_url('options-permalink.php') . '" target="_blank">Go to Permalinks and click "Save Changes"</a></li>';
echo '<li>Try deactivating and reactivating the plugin</li>';
echo '</ol>';
echo '</div>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Test URLs</h2>';

echo '<p>After running fixes, test these URLs:</p>';
echo '<ul>';
echo '<li><a href="' . home_url('/store-manager/') . '" target="_blank">Store Manager</a></li>';
echo '<li><a href="' . home_url('/update-store/') . '" target="_blank">Update Store</a></li>';
echo '<li><a href="' . home_url('/?sllist_page=store_manager') . '" target="_blank">Store Manager (Query)</a></li>';
echo '<li><a href="' . home_url('/?sllist_page=store_update') . '" target="_blank">Update Store (Query)</a></li>';
echo '</ul>';

echo '</div>';

?>

</body>
</html>
