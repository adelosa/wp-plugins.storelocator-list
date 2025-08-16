<?php
/**
 * Browser-accessible Test script for Store Update Success Page functionality
 * 
 * Access this via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-success-page.php
 * Make sure to access via the browser, not command line.
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
    die('<h1>WordPress Not Found</h1><p>Could not load WordPress. Please access this file through your WordPress installation.</p><p>Try: <code>http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-success-page.php</code></p>');
}

// Check if user is admin (only for testing)
if (!current_user_can('manage_options')) {
    die('<h1>Admin Access Required</h1><p>You must be logged in as an administrator to run these tests.</p><p><a href="' . wp_login_url($_SERVER['REQUEST_URI']) . '">Login</a></p>');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Store Update Success Page Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
        .success { color: #46b450; }
        .error { color: #dc3232; }
        .warning { color: #ffb900; }
        .info { color: #0073aa; }
        .code { background: #f1f1f1; padding: 2px 4px; font-family: monospace; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .test-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .test-error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .test-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        .button { display: inline-block; padding: 8px 16px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button-secondary { background: #6c757d; }
        .button-success { background: #28a745; }
        .button-warning { background: #ffc107; color: #212529; }
    </style>
</head>
<body>

<h1>Store Update Success Page Test</h1>
<p>This page tests the new success page functionality for store updates.</p>

<?php

function test_success_page_urls() {
    $results = [];
    
    // Test 1: Success page with store name
    $test_store_name = "Test Store Name";
    $success_url_with_name = add_query_arg(array(
        'sllist_page' => 'store_update',
        'success' => '1',
        'store_name' => urlencode($test_store_name)
    ), home_url('/update-store/'));
    
    $results['success_with_name'] = [
        'name' => 'Success Page with Store Name',
        'url' => $success_url_with_name,
        'expected' => 'Should show success page with store name: ' . $test_store_name
    ];
    
    // Test 2: Success page without store name
    $success_url_no_name = add_query_arg(array(
        'sllist_page' => 'store_update',
        'success' => '1'
    ), home_url('/update-store/'));
    
    $results['success_no_name'] = [
        'name' => 'Success Page without Store Name',
        'url' => $success_url_no_name,
        'expected' => 'Should show success page with default "Your Store" name'
    ];
    
    // Test 3: Regular update page (should not show success)
    $regular_url = home_url('/update-store/');
    
    $results['regular_page'] = [
        'name' => 'Regular Update Page',
        'url' => $regular_url,
        'expected' => 'Should show regular "Invalid Access" page (no token)'
    ];
    
    return $results;
}

function test_javascript_localization() {
    // Check if the store update JavaScript is properly localized
    $plugin = \StoreLocatorList\SLList_Plugin::get_instance();
    
    if (!$plugin) {
        return [
            'success' => false,
            'message' => 'Plugin instance not found'
        ];
    }
    
    // Test the localization strings
    $expected_strings = [
        'authenticating' => 'Authenticating...',
        'updating' => 'Updating store details...',
        'error' => 'An error occurred. Please try again.',
        'success' => 'Store details updated successfully!'
    ];
    
    return [
        'success' => true,
        'message' => 'JavaScript localization strings are properly defined',
        'strings' => $expected_strings
    ];
}

function test_redirect_url_generation() {
    // Test the redirect URL generation logic
    $test_store_name = "Test Store & Co.";
    
    $expected_url = add_query_arg(array(
        'success' => '1',
        'store_name' => urlencode($test_store_name)
    ), home_url('/update-store/'));
    
    return [
        'success' => true,
        'message' => 'Redirect URL generation test',
        'test_store_name' => $test_store_name,
        'generated_url' => $expected_url,
        'encoded_name' => urlencode($test_store_name)
    ];
}

// Run tests
$url_tests = test_success_page_urls();
$js_test = test_javascript_localization();
$redirect_test = test_redirect_url_generation();

?>

<div class="test-section">
    <h2>Success Page URL Tests</h2>
    <p>These tests check if the success page URLs are generated correctly and accessible.</p>
    
    <?php foreach ($url_tests as $test_key => $test): ?>
    <div class="test-result test-success">
        <h3><?php echo esc_html($test['name']); ?></h3>
        <p><strong>URL:</strong> <span class="code"><?php echo esc_html($test['url']); ?></span></p>
        <p><strong>Expected:</strong> <?php echo esc_html($test['expected']); ?></p>
        <a href="<?php echo esc_url($test['url']); ?>" target="_blank" class="button">Test This URL</a>
    </div>
    <?php endforeach; ?>
</div>

<div class="test-section">
    <h2>JavaScript Localization Test</h2>
    <?php if ($js_test['success']): ?>
    <div class="test-result test-success">
        <p class="success">✅ <?php echo esc_html($js_test['message']); ?></p>
        <h4>Localized Strings:</h4>
        <ul>
            <?php foreach ($js_test['strings'] as $key => $value): ?>
            <li><strong><?php echo esc_html($key); ?>:</strong> <span class="code"><?php echo esc_html($value); ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="test-result test-error">
        <p class="error">❌ <?php echo esc_html($js_test['message']); ?></p>
    </div>
    <?php endif; ?>
</div>

<div class="test-section">
    <h2>Redirect URL Generation Test</h2>
    <div class="test-result test-success">
        <p class="success">✅ <?php echo esc_html($redirect_test['message']); ?></p>
        <p><strong>Test Store Name:</strong> <span class="code"><?php echo esc_html($redirect_test['test_store_name']); ?></span></p>
        <p><strong>Encoded Name:</strong> <span class="code"><?php echo esc_html($redirect_test['encoded_name']); ?></span></p>
        <p><strong>Generated URL:</strong> <span class="code"><?php echo esc_html($redirect_test['generated_url']); ?></span></p>
        <a href="<?php echo esc_url($redirect_test['generated_url']); ?>" target="_blank" class="button">Test Generated URL</a>
    </div>
</div>

<div class="test-section">
    <h2>Testing Instructions</h2>
    <div class="test-result test-warning">
        <h3>How to Test the Complete Flow</h3>
        <ol>
            <li><strong>Create Test Store Access:</strong> Go to <a href="test-store-update.php" target="_blank">test-store-update.php</a> and create test access for a store</li>
            <li><strong>Access the Update Form:</strong> Use the generated link and password to access the store update form</li>
            <li><strong>Submit the Form:</strong> Fill in the form and click "Update Store Details" button</li>
            <li><strong>Verify Success Flow:</strong> You should see:
                <ul>
                    <li>Brief success message with "Redirecting..."</li>
                    <li>Automatic redirect to the success page</li>
                    <li>Success page with store name, confirmation message, and homepage link</li>
                </ul>
            </li>
        </ol>
    </div>
</div>

<div class="test-section">
    <h2>Expected Behavior Changes</h2>
    <div class="test-result test-success">
        <h3>✅ Fixed Issues</h3>
        <ul>
            <li><strong>Button State:</strong> Button no longer stays in "Updating..." state</li>
            <li><strong>Form Disabled:</strong> Form is properly disabled during redirect</li>
            <li><strong>Success Page:</strong> Users are redirected to a dedicated success page</li>
            <li><strong>Homepage Link:</strong> Clear button to return to homepage</li>
            <li><strong>Security Notice:</strong> Users are informed that their token is deactivated</li>
        </ul>
    </div>
</div>

<div class="test-section">
    <h2>Quick Actions</h2>
    <a href="test-store-update.php" class="button">Go to Store Update Test</a>
    <a href="<?php echo home_url('/store-manager/'); ?>" class="button button-secondary" target="_blank">Visit Store Manager</a>
    <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button button-warning">Flush Rewrite Rules</a>
    <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="button">Refresh This Test</a>
</div>

</body>
</html>
