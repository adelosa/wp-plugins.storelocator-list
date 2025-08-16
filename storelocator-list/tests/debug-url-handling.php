<?php
/**
 * Debug URL Rewrite Issues
 * 
 * This script helps debug what's happening with the pretty URL handling
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-url-handling.php
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
    <title>Debug URL Handling</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #46b450; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3232; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
    </style>
</head>
<body>

<h1>Debug URL Handling</h1>

<?php

echo '<div class="test-section">';
echo '<h2>🔍 Current Request Analysis</h2>';

global $wp_query, $wp;

echo '<h3>Current Request:</h3>';
echo '<p><strong>Request URI:</strong> ' . esc_html($_SERVER['REQUEST_URI'] ?? '') . '</p>';
echo '<p><strong>Query String:</strong> ' . esc_html($_SERVER['QUERY_STRING'] ?? '') . '</p>';
echo '<p><strong>HTTP Host:</strong> ' . esc_html($_SERVER['HTTP_HOST'] ?? '') . '</p>';

echo '<h3>WordPress Query Object:</h3>';
echo '<p><strong>Matched Rule:</strong> ' . esc_html($wp->matched_rule ?? 'None') . '</p>';
echo '<p><strong>Matched Query:</strong> ' . esc_html($wp->matched_query ?? 'None') . '</p>';
echo '<p><strong>Request:</strong> ' . esc_html($wp->request ?? 'None') . '</p>';

echo '<h3>Query Variables:</h3>';
if (isset($wp->query_vars) && !empty($wp->query_vars)) {
    echo '<div class="code">';
    foreach ($wp->query_vars as $key => $value) {
        if (!empty($value)) {
            echo esc_html($key) . ' = ' . esc_html($value) . '<br>';
        }
    }
    echo '</div>';
} else {
    echo '<p>No query variables set</p>';
}

echo '<h3>Plugin-Specific Query Vars:</h3>';
$sllist_page = get_query_var('sllist_page');
echo '<p><strong>sllist_page:</strong> ' . (empty($sllist_page) ? 'Not set' : esc_html($sllist_page)) . '</p>';

// Check $_GET as fallback
if (isset($_GET['sllist_page'])) {
    echo '<p><strong>$_GET[\'sllist_page\']:</strong> ' . esc_html($_GET['sllist_page']) . '</p>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🛠️ Debug Actions</h2>';

// Test rewrite rule matching
echo '<h3>Test URL Parsing:</h3>';

$test_urls = [
    '/store-manager/' => 'store-manager',
    '/update-store/' => 'update-store',
    '/?sllist_page=store_manager' => 'fallback store manager',
    '/?sllist_page=store_update' => 'fallback store update'
];

foreach ($test_urls as $url => $description) {
    echo '<p><strong>' . esc_html($description) . ':</strong> ';
    
    // Simulate the URL parsing
    $parsed_url = parse_url($url);
    $path = $parsed_url['path'] ?? '';
    $query = $parsed_url['query'] ?? '';
    
    // Check if it matches our rewrite rules
    $rewrite_rules = get_option('rewrite_rules', []);
    $matched = false;
    
    foreach ($rewrite_rules as $pattern => $replacement) {
        if (preg_match("#^$pattern#", ltrim($path, '/'))) {
            echo '<span style="color: green;">✅ Matches pattern: ' . esc_html($pattern) . ' → ' . esc_html($replacement) . '</span>';
            $matched = true;
            break;
        }
    }
    
    if (!$matched && !empty($query)) {
        parse_str($query, $query_vars);
        if (isset($query_vars['sllist_page'])) {
            echo '<span style="color: blue;">ℹ️ Uses query parameter: ' . esc_html($query_vars['sllist_page']) . '</span>';
        }
    }
    
    if (!$matched && empty($query)) {
        echo '<span style="color: red;">❌ No matching rule found</span>';
    }
    
    echo '</p>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Live Tests</h2>';

echo '<p>Test these URLs to see what happens:</p>';
echo '<ul>';
echo '<li><a href="' . home_url('/store-manager/') . '" target="_blank">' . home_url('/store-manager/') . '</a></li>';
echo '<li><a href="' . home_url('/update-store/') . '" target="_blank">' . home_url('/update-store/') . '</a></li>';
echo '<li><a href="' . home_url('/?sllist_page=store_manager') . '" target="_blank">' . home_url('/?sllist_page=store_manager') . '</a></li>';
echo '<li><a href="' . home_url('/?sllist_page=store_update') . '" target="_blank">' . home_url('/?sllist_page=store_update') . '</a></li>';
echo '</ul>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 Potential Fixes</h2>';

// Check if we need to use a different approach
echo '<p>If the pretty URLs are showing the theme\'s 404 page instead of our custom pages, try these solutions:</p>';

echo '<h3>Option 1: Force Template Override</h3>';
echo '<div class="code">';
echo 'Add this to your theme\'s functions.php temporarily for testing:<br><br>';
echo 'add_action(\'template_redirect\', function() {<br>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;$request = trim($_SERVER[\'REQUEST_URI\'], \'/\');<br>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;if ($request === \'store-manager\') {<br>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo \'DEBUG: store-manager URL detected!\';<br>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;exit;<br>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;}<br>';
echo '}, 1);';
echo '</div>';

echo '<h3>Option 2: Check Template Hierarchy</h3>';
echo '<p>WordPress might be loading a theme template. We can override this by using the <code>template_include</code> filter.</p>';

echo '<h3>Option 3: Debug Hooks</h3>';
echo '<p>Add debug output to see which hooks are firing:</p>';
echo '<div class="code">';
echo 'add_action(\'wp\', function() {<br>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;error_log(\'WP Hook: \' . print_r($_SERVER[\'REQUEST_URI\'], true));<br>';
echo '});<br><br>';
echo 'add_action(\'template_redirect\', function() {<br>';
echo '&nbsp;&nbsp;&nbsp;&nbsp;error_log(\'Template Redirect: \' . get_query_var(\'sllist_page\'));<br>';
echo '}, 1);';
echo '</div>';

echo '</div>';

?>

<script>
// Auto-refresh every 10 seconds for debugging
setTimeout(function() {
    if (confirm('Refresh page to see updated debug info?')) {
        window.location.reload();
    }
}, 10000);
</script>

</body>
</html>
