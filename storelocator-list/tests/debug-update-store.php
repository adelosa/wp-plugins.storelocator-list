<?php
/**
 * Debug Update Store URL
 * 
 * This script helps debug why update-store page shows a blog post instead of our custom page
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/debug-update-store.php
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
    <title>Debug Update Store URL</title>
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
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>

<h1>Debug Update Store URL</h1>

<?php

echo '<div class="test-section">';
echo '<h2>🔍 Rewrite Rules Check</h2>';

$rewrite_rules = get_option('rewrite_rules', []);
$found_update_rules = [];

foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'update-store') !== false) {
        $found_update_rules[$pattern] = $replacement;
    }
}

if (!empty($found_update_rules)) {
    echo '<div class="success">';
    echo '<h3>✅ Update Store Rules Found</h3>';
    echo '<table>';
    echo '<tr><th>Pattern</th><th>Replacement</th></tr>';
    foreach ($found_update_rules as $pattern => $replacement) {
        echo '<tr>';
        echo '<td><code>' . esc_html($pattern) . '</code></td>';
        echo '<td><code>' . esc_html($replacement) . '</code></td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="error">❌ No update-store rewrite rules found!</div>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Manual URL Testing</h2>';

// Simulate what happens when we visit /update-store/
echo '<h3>Testing /update-store/ URL Resolution</h3>';

$test_url = 'update-store/';
$wp_rewrite = new WP_Rewrite();

echo '<p><strong>Test URL:</strong> <code>' . esc_html($test_url) . '</code></p>';

// Check if the URL matches our pattern
$pattern = '^update-store/?$';
$matches = preg_match('#' . $pattern . '#', $test_url);
echo '<p><strong>Pattern Match:</strong> ' . ($matches ? '✅ Matches' : '❌ No match') . '</p>';

// Test WordPress's URL parsing
$wp = new WP();
$wp->parse_request('update-store/');

echo '<h4>WordPress Parse Result:</h4>';
echo '<pre>';
echo 'Query vars: ' . print_r($wp->query_vars, true);
echo 'Matched rule: ' . $wp->matched_rule . "\n";
echo 'Matched query: ' . $wp->matched_query . "\n";
echo '</pre>';

// Check if our query var is set
if (isset($wp->query_vars['sllist_page'])) {
    echo '<div class="success">✅ sllist_page found: ' . esc_html($wp->query_vars['sllist_page']) . '</div>';
} else {
    echo '<div class="error">❌ sllist_page not found in query vars</div>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>📋 Check for Conflicting Content</h2>';

// Check if there's a page or post with slug "update-store"
$existing_post = get_page_by_path('update-store');
if ($existing_post) {
    echo '<div class="error">';
    echo '<h3>❌ CONFLICT FOUND!</h3>';
    echo '<p>There is an existing WordPress ' . $existing_post->post_type . ' with the slug "update-store":</p>';
    echo '<ul>';
    echo '<li><strong>Title:</strong> ' . esc_html($existing_post->post_title) . '</li>';
    echo '<li><strong>ID:</strong> ' . $existing_post->ID . '</li>';
    echo '<li><strong>Status:</strong> ' . $existing_post->post_status . '</li>';
    echo '<li><strong>Type:</strong> ' . $existing_post->post_type . '</li>';
    echo '</ul>';
    echo '<p><strong>Solution:</strong> You need to either:</p>';
    echo '<ol>';
    echo '<li>Delete or rename this ' . $existing_post->post_type . '</li>';
    echo '<li>Change our URL pattern to something else (e.g., "store-update" instead of "update-store")</li>';
    echo '</ol>';
    echo '</div>';
} else {
    echo '<div class="success">✅ No conflicting pages or posts found with slug "update-store"</div>';
}

// Also check for other post types
$custom_posts = get_posts([
    'name' => 'update-store',
    'post_type' => 'any',
    'post_status' => 'any',
    'numberposts' => 1
]);

if (!empty($custom_posts)) {
    $post = $custom_posts[0];
    echo '<div class="warning">';
    echo '<h3>⚠️ Potential Conflict Found</h3>';
    echo '<p>Found a ' . $post->post_type . ' with slug "update-store":</p>';
    echo '<ul>';
    echo '<li><strong>Title:</strong> ' . esc_html($post->post_title) . '</li>';
    echo '<li><strong>ID:</strong> ' . $post->ID . '</li>';
    echo '<li><strong>Status:</strong> ' . $post->post_status . '</li>';
    echo '<li><strong>Type:</strong> ' . $post->post_type . '</li>';
    echo '</ul>';
    echo '</div>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 Class Status Check</h2>';

// Check if our classes are loaded
$store_update_class = 'StoreLocatorList\\PublicPages\\SLList_Store_Update';
echo '<p><strong>SLList_Store_Update class:</strong> ' . (class_exists($store_update_class) ? '✅ Loaded' : '❌ Missing') . '</p>';

if (class_exists($store_update_class)) {
    // Check if hooks are registered
    global $wp_filter;
    
    $hooks_to_check = [
        'parse_request',
        'template_redirect', 
        'template_include',
        'wp_title',
        'document_title_parts'
    ];
    
    echo '<h3>Hook Registration Status</h3>';
    echo '<table>';
    echo '<tr><th>Hook</th><th>Registered</th><th>Priority</th></tr>';
    
    foreach ($hooks_to_check as $hook) {
        $registered = false;
        $priority = 'N/A';
        
        if (isset($wp_filter[$hook])) {
            foreach ($wp_filter[$hook]->callbacks as $prio => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        get_class($callback['function'][0]) === $store_update_class) {
                        $registered = true;
                        $priority = $prio;
                        break 2;
                    }
                }
            }
        }
        
        echo '<tr>';
        echo '<td><code>' . esc_html($hook) . '</code></td>';
        echo '<td>' . ($registered ? '✅ Yes' : '❌ No') . '</td>';
        echo '<td>' . esc_html($priority) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🧪 Test URLs</h2>';

echo '<p>Try these URLs to test the update-store functionality:</p>';
echo '<ul>';
echo '<li><a href="' . home_url('/update-store/') . '" target="_blank">Pretty URL: /update-store/</a></li>';
echo '<li><a href="' . home_url('/?sllist_page=store_update') . '" target="_blank">Query Parameter: /?sllist_page=store_update</a></li>';
echo '</ul>';

echo '<p><strong>Expected behavior:</strong> Both URLs should show the store update page, not a blog post.</p>';

echo '</div>';

?>

</body>
</html>
