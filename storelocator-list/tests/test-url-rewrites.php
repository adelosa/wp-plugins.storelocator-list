<?php
/**
 * URL Rewrite Configuration and Test Script
 * 
 * This script helps configure and test URL rewrites for the Store Locator plugin
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-url-rewrites.php
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
    <title>URL Rewrite Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: #46b450; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3232; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .test-section { border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #005a87; }
        .button-secondary { background: #6c757d; }
        .button-secondary:hover { background: #545b62; }
    </style>
</head>
<body>

<h1>URL Rewrite Configuration Test</h1>

<?php

// Auto-configure permalinks if needed
if (isset($_GET['auto_configure'])) {
    echo '<div class="info"><h3>🔧 Auto-configuring URL rewrites...</h3></div>';
    
    // Set permalink structure to Post name
    update_option('permalink_structure', '/%postname%/');
    
    // Flush rewrite rules
    flush_rewrite_rules(true);
    
    echo '<div class="success">✅ Permalink structure set to "Post name" and rewrite rules flushed!</div>';
    echo '<p><a href="?test_urls=1" class="button">Test URLs Now</a></p>';
}

// Test URLs if requested
if (isset($_GET['test_urls'])) {
    echo '<div class="test-section">';
    echo '<h3>🧪 Testing URLs...</h3>';
    
    $test_urls = [
        'Store Manager (Pretty)' => home_url('/store-manager/'),
        'Store Manager (Fallback)' => home_url('/?sllist_page=store_manager'),
        'Update Store (Pretty)' => home_url('/update-store/'),
        'Update Store (Fallback)' => home_url('/?sllist_page=store_update')
    ];
    
    echo '<table>';
    echo '<tr><th>URL Type</th><th>URL</th><th>Test</th></tr>';
    
    foreach ($test_urls as $name => $url) {
        echo '<tr>';
        echo '<td>' . esc_html($name) . '</td>';
        echo '<td><code>' . esc_html($url) . '</code></td>';
        echo '<td><a href="' . esc_attr($url) . '" target="_blank" class="button button-secondary">Test</a></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</div>';
}

echo '<div class="test-section">';
echo '<h2>🔍 Current Configuration</h2>';

// Check permalink structure
$permalink_structure = get_option('permalink_structure');
if (empty($permalink_structure)) {
    echo '<div class="error">❌ <strong>Permalinks are set to "Plain"</strong> - Pretty URLs will not work!</div>';
    echo '<p>You need to enable pretty permalinks for custom URL rewrites to work.</p>';
    echo '<p><a href="?auto_configure=1" class="button">Auto-Configure Pretty URLs</a></p>';
} else {
    echo '<div class="success">✅ <strong>Pretty permalinks enabled:</strong> ' . esc_html($permalink_structure) . '</div>';
}

echo '<p><strong>Current permalink structure:</strong> ' . (empty($permalink_structure) ? 'Plain' : esc_html($permalink_structure)) . '</p>';

// Check .htaccess
$htaccess_path = ABSPATH . '.htaccess';
$htaccess_exists = file_exists($htaccess_path);
$htaccess_writable = is_writable($htaccess_path) || (!$htaccess_exists && is_writable(ABSPATH));

echo '<p><strong>.htaccess file:</strong> ';
if ($htaccess_exists) {
    echo '✅ Exists';
    if ($htaccess_writable) {
        echo ' and writable';
    } else {
        echo ' but not writable';
    }
} else {
    echo '❌ Does not exist';
    if ($htaccess_writable) {
        echo ' (but directory is writable)';
    } else {
        echo ' (and directory is not writable)';
    }
}
echo '</p>';

// Show .htaccess content if it exists
if ($htaccess_exists) {
    $htaccess_content = file_get_contents($htaccess_path);
    echo '<h3>.htaccess Content:</h3>';
    echo '<div class="code">' . esc_html($htaccess_content) . '</div>';
    
    if (strpos($htaccess_content, 'RewriteEngine On') === false) {
        echo '<div class="warning">⚠️ RewriteEngine is not enabled in .htaccess</div>';
    }
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🔧 Plugin Rewrite Rules</h2>';

// Check if our rewrite rules are registered
$rewrite_rules = get_option('rewrite_rules', []);
$found_rules = [];

foreach ($rewrite_rules as $pattern => $replacement) {
    if (strpos($pattern, 'store-manager') !== false || strpos($pattern, 'update-store') !== false) {
        $found_rules[] = ['pattern' => $pattern, 'replacement' => $replacement];
    }
}

if (empty($found_rules)) {
    echo '<div class="error">❌ <strong>No store-related rewrite rules found!</strong></div>';
    echo '<p>The plugin rewrite rules are not registered. Try flushing rewrite rules.</p>';
} else {
    echo '<div class="success">✅ <strong>Found ' . count($found_rules) . ' store-related rewrite rules:</strong></div>';
    echo '<table>';
    echo '<tr><th>Pattern</th><th>Replacement</th></tr>';
    foreach ($found_rules as $rule) {
        echo '<tr>';
        echo '<td><code>' . esc_html($rule['pattern']) . '</code></td>';
        echo '<td><code>' . esc_html($rule['replacement']) . '</code></td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🚀 Actions</h2>';

echo '<p><a href="?auto_configure=1" class="button">Auto-Configure Pretty URLs</a></p>';
echo '<p><a href="' . admin_url('options-permalink.php') . '" class="button button-secondary" target="_blank">WordPress Permalink Settings</a></p>';
echo '<p><a href="' . admin_url('tools.php?page=sllist-store-update-test') . '" class="button button-secondary" target="_blank">Store Update Test Page</a></p>';

// Add flush rewrite rules button
echo '<form method="post" style="display: inline;">';
echo '<input type="hidden" name="action" value="flush_rewrite_rules">';
echo '<input type="submit" class="button" value="Flush Rewrite Rules">';
echo '</form>';

if (isset($_POST['action']) && $_POST['action'] === 'flush_rewrite_rules') {
    flush_rewrite_rules(true);
    echo '<div class="success">✅ Rewrite rules flushed! <a href="?" class="button button-secondary">Refresh Page</a></div>';
}

echo '<p><a href="?test_urls=1" class="button button-secondary">Test URLs</a></p>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>📋 Manual Configuration Steps</h2>';

echo '<p>If auto-configuration doesn\'t work, follow these steps:</p>';
echo '<ol>';
echo '<li><strong>Enable Pretty Permalinks:</strong> Go to <a href="' . admin_url('options-permalink.php') . '" target="_blank">Settings → Permalinks</a> and select "Post name" or "Custom Structure"</li>';
echo '<li><strong>Make sure .htaccess is writable:</strong> The file should be writable by the web server</li>';
echo '<li><strong>Flush rewrite rules:</strong> Click the "Flush Rewrite Rules" button above</li>';
echo '<li><strong>Test URLs:</strong> Try accessing /store-manager/ and /update-store/</li>';
echo '</ol>';

echo '</div>';

echo '<div class="test-section">';
echo '<h2>🐳 Docker-Specific Notes</h2>';

echo '<p>Since you\'re running WordPress in Docker, make sure:</p>';
echo '<ul>';
echo '<li><strong>Apache mod_rewrite is enabled:</strong> The WordPress Docker image should have this enabled by default</li>';
echo '<li><strong>Volume permissions:</strong> The .htaccess file needs to be writable in the container</li>';
echo '<li><strong>Container restart:</strong> Sometimes you may need to restart the WordPress container after changing .htaccess</li>';
echo '</ul>';

echo '<div class="code">';
echo '# To restart your WordPress container:<br>';
echo 'docker-compose restart wordpress<br><br>';
echo '# To check if mod_rewrite is enabled:<br>';
echo 'docker exec wordpress apache2ctl -M | grep rewrite';
echo '</div>';

echo '</div>';

?>

<script>
// Auto-refresh test results every 30 seconds if testing
if (window.location.search.includes('test_urls=1')) {
    setTimeout(function() {
        window.location.reload();
    }, 30000);
}
</script>

</body>
</html>
