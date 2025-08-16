<?php
/**
 * Browser-accessible Test script for Store Update functionality
 * 
 * Access this via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-store-update.php
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
    die('<h1>WordPress Not Found</h1><p>Could not load WordPress. Please access this file through your WordPress installation.</p><p>Try: <code>http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-store-update.php</code></p>');
}

// Check if user is admin (only for testing)
if (!current_user_can('manage_options')) {
    die('<h1>Admin Access Required</h1><p>You must be logged in as an administrator to run these tests.</p><p><a href="' . wp_login_url($_SERVER['REQUEST_URI']) . '">Login</a></p>');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Store Update Functionality Test</title>
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
        .test-info { background: #d1ecf1; border: 1px solid #bee5eb; }
        .button { display: inline-block; padding: 8px 16px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button:hover { background: #005a87; }
        .button-secondary { background: #6c757d; }
        .button-success { background: #28a745; }
        .button-warning { background: #ffc107; color: #212529; }
    </style>
</head>
<body>

<h1>Store Update Functionality Test</h1>
<p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?> | <strong>Site URL:</strong> <?php echo home_url(); ?></p>

<?php

$test_results = array();

// Test 1: Class Loading
$test_results['class_loading'] = test_class_loading();

// Test 2: Asset Files
$test_results['asset_files'] = test_asset_files();

// Test 3: URL Generation
$test_results['url_generation'] = test_url_generation();

// Test 4: Rewrite Rules
$test_results['rewrite_rules'] = test_rewrite_rules();

// Test 5: Security Functions
$test_results['security_functions'] = test_security_functions();

// Test 6: Store Creation and Access
$test_results['store_access'] = test_store_access();

// Display results
display_test_results($test_results);

// Test Functions

function test_class_loading() {
    $classes_to_test = array(
        'StoreLocatorList\PublicPages\SLList_Store_Update',
        'StoreLocatorList\PublicPages\SLList_Store_Search', 
        'StoreLocatorList\Core\SLList_Security',
        'StoreLocatorList\SLList_Plugin'
    );
    
    $results = array();
    $all_loaded = true;
    
    foreach ($classes_to_test as $class) {
        $exists = class_exists($class);
        $results[] = array(
            'class' => $class,
            'loaded' => $exists
        );
        if (!$exists) $all_loaded = false;
    }
    
    return array(
        'success' => $all_loaded,
        'message' => $all_loaded ? 'All classes loaded successfully' : 'Some classes failed to load',
        'details' => $results
    );
}

function test_asset_files() {
    $plugin_path = plugin_dir_path(dirname(__FILE__)) . 'storelocator-list/';
    
    $files_to_test = array(
        'assets/css/store-update.css',
        'assets/js/store-update.js',
        'includes/public/class-sllist-store-update.php'
    );
    
    $results = array();
    $all_exist = true;
    
    foreach ($files_to_test as $file) {
        $full_path = $plugin_path . $file;
        $exists = file_exists($full_path);
        $results[] = array(
            'file' => $file,
            'exists' => $exists,
            'path' => $full_path
        );
        if (!$exists) $all_exist = false;
    }
    
    return array(
        'success' => $all_exist,
        'message' => $all_exist ? 'All asset files found' : 'Some asset files are missing',
        'details' => $results
    );
}

function test_url_generation() {
    try {
        $test_token = 'test-token-12345';
        
        $urls = array();
        
        if (class_exists('StoreLocatorList\PublicPages\SLList_Store_Search')) {
            $urls[] = array(
                'name' => 'Store Manager (pretty)',
                'url' => \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url(true)
            );
            $urls[] = array(
                'name' => 'Store Manager (fallback)', 
                'url' => \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url(false)
            );
        }
        
        if (class_exists('StoreLocatorList\PublicPages\SLList_Store_Update')) {
            $urls[] = array(
                'name' => 'Store Update (pretty)',
                'url' => \StoreLocatorList\PublicPages\SLList_Store_Update::get_store_update_url($test_token, true)
            );
            $urls[] = array(
                'name' => 'Store Update (fallback)',
                'url' => \StoreLocatorList\PublicPages\SLList_Store_Update::get_store_update_url($test_token, false)
            );
        }
        
        return array(
            'success' => !empty($urls),
            'message' => !empty($urls) ? 'URL generation working' : 'URL generation failed',
            'details' => $urls
        );
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'URL generation error: ' . $e->getMessage(),
            'details' => array()
        );
    }
}

function test_rewrite_rules() {
    $rewrite_rules = get_option('rewrite_rules', array());
    $found_rules = array();
    
    foreach ($rewrite_rules as $pattern => $replacement) {
        if (strpos($pattern, 'store-manager') !== false || strpos($pattern, 'update-store') !== false) {
            $found_rules[] = array(
                'pattern' => $pattern,
                'replacement' => $replacement
            );
        }
    }
    
    $recommendations = array();
    if (empty($found_rules)) {
        $recommendations[] = 'No store-related rewrite rules found. You may need to flush rewrite rules.';
        $recommendations[] = 'Go to Settings → Permalinks and click "Save Changes" to flush rewrite rules.';
    }
    
    return array(
        'success' => !empty($found_rules),
        'message' => !empty($found_rules) ? count($found_rules) . ' rewrite rules found' : 'No rewrite rules found',
        'details' => $found_rules,
        'recommendations' => $recommendations
    );
}

function test_security_functions() {
    try {
        if (!class_exists('StoreLocatorList\Core\SLList_Security')) {
            throw new Exception('Security class not found');
        }
        
        $token = \StoreLocatorList\Core\SLList_Security::generate_access_token();
        $password = \StoreLocatorList\Core\SLList_Security::generate_access_password();
        $hash = \StoreLocatorList\Core\SLList_Security::hash_password($password);
        $hash_valid = \StoreLocatorList\Core\SLList_Security::verify_password($password, $hash);
        
        return array(
            'success' => $hash_valid,
            'message' => $hash_valid ? 'Security functions working correctly' : 'Security functions failed verification',
            'details' => array(
                'token_length' => strlen($token),
                'password_length' => strlen($password),
                'hash_valid' => $hash_valid,
                'sample_token' => substr($token, 0, 20) . '...',
                'sample_password' => $password
            )
        );
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Security functions error: ' . $e->getMessage(),
            'details' => array()
        );
    }
}

function test_store_access() {
    // Check if we have any stores
    $stores = get_posts(array(
        'post_type' => 'wpsl_stores',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if (empty($stores)) {
        return array(
            'success' => false,
            'message' => 'No stores found for testing',
            'details' => array(),
            'recommendations' => array('Create a test store to fully test the access functionality')
        );
    }
    
    $test_store = $stores[0];
    
    // Try to create access credentials
    try {
        if (!class_exists('StoreLocatorList\Core\SLList_Security')) {
            throw new Exception('Security class not found');
        }
        
        $credentials = \StoreLocatorList\Core\SLList_Security::create_store_access_credentials($test_store->ID);
        
        if ($credentials) {
            $test_url = '';
            if (class_exists('StoreLocatorList\PublicPages\SLList_Store_Update')) {
                $test_url = \StoreLocatorList\PublicPages\SLList_Store_Update::get_store_update_url($credentials['token']);
            }
            
            return array(
                'success' => true,
                'message' => 'Successfully created test access credentials',
                'details' => array(
                    'store_name' => $test_store->post_title,
                    'store_id' => $test_store->ID,
                    'token' => $credentials['token'],
                    'password' => $credentials['password'],
                    'test_url' => $test_url
                )
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Failed to create access credentials',
                'details' => array()
            );
        }
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'Store access test error: ' . $e->getMessage(),
            'details' => array()
        );
    }
}

function display_test_results($results) {
    foreach ($results as $test_name => $result) {
        echo '<div class="test-section">';
        
        $class = $result['success'] ? 'test-success' : 'test-error';
        $icon = $result['success'] ? '✓' : '✗';
        
        echo '<h2>' . $icon . ' ' . ucwords(str_replace('_', ' ', $test_name)) . '</h2>';
        echo '<div class="test-result ' . $class . '">';
        echo '<p><strong>Result:</strong> ' . $result['message'] . '</p>';
        
        if (!empty($result['details'])) {
            echo '<h4>Details:</h4>';
            if ($test_name === 'class_loading') {
                echo '<ul>';
                foreach ($result['details'] as $detail) {
                    $status = $detail['loaded'] ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
                    echo '<li>' . $status . ' ' . $detail['class'] . '</li>';
                }
                echo '</ul>';
            } elseif ($test_name === 'asset_files') {
                echo '<ul>';
                foreach ($result['details'] as $detail) {
                    $status = $detail['exists'] ? '<span class="success">✓</span>' : '<span class="error">✗</span>';
                    echo '<li>' . $status . ' ' . $detail['file'] . '</li>';
                }
                echo '</ul>';
            } elseif ($test_name === 'url_generation') {
                echo '<ul>';
                foreach ($result['details'] as $detail) {
                    echo '<li><strong>' . $detail['name'] . ':</strong><br>';
                    echo '<a href="' . esc_url($detail['url']) . '" target="_blank" class="button button-secondary">' . esc_html($detail['url']) . '</a></li>';
                }
                echo '</ul>';
            } elseif ($test_name === 'rewrite_rules') {
                if (!empty($result['details'])) {
                    echo '<ul>';
                    foreach ($result['details'] as $detail) {
                        echo '<li><span class="code">' . esc_html($detail['pattern']) . '</span> → <span class="code">' . esc_html($detail['replacement']) . '</span></li>';
                    }
                    echo '</ul>';
                }
            } elseif ($test_name === 'security_functions') {
                echo '<ul>';
                foreach ($result['details'] as $key => $value) {
                    if (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    }
                    echo '<li><strong>' . ucwords(str_replace('_', ' ', $key)) . ':</strong> <span class="code">' . esc_html($value) . '</span></li>';
                }
                echo '</ul>';
            } elseif ($test_name === 'store_access') {
                if ($result['success']) {
                    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 4px;">';
                    echo '<h4>Test Access Created!</h4>';
                    echo '<p><strong>Store:</strong> ' . esc_html($result['details']['store_name']) . '</p>';
                    echo '<p><strong>Password:</strong> <span class="code">' . esc_html($result['details']['password']) . '</span></p>';
                    if (!empty($result['details']['test_url'])) {
                        echo '<p><strong>Test URL:</strong></p>';
                        echo '<a href="' . esc_url($result['details']['test_url']) . '" target="_blank" class="button button-success">Test Store Update Page</a>';
                        echo '<p><small>Click the button above and use the password to test the store update functionality.</small></p>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>' . esc_html($result['message']) . '</p>';
                }
            }
        }
        
        if (!empty($result['recommendations'])) {
            echo '<h4>Recommendations:</h4>';
            echo '<ul>';
            foreach ($result['recommendations'] as $rec) {
                echo '<li class="warning">' . esc_html($rec) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>'; // .test-result
        echo '</div>'; // .test-section
    }
    
    // Add action buttons
    echo '<div class="test-section">';
    echo '<h2>Actions</h2>';
    echo '<a href="' . admin_url('options-permalink.php') . '" class="button">Flush Rewrite Rules</a>';
    echo '<a href="' . home_url('/store-manager/') . '" class="button button-secondary" target="_blank">Visit Store Manager</a>';
    echo '<a href="' . home_url('/update-store/') . '" class="button button-secondary" target="_blank">Visit Update Page (no token)</a>';
    echo '<a href="' . $_SERVER['REQUEST_URI'] . '" class="button button-warning">Refresh Tests</a>';
    echo '</div>';
}

?>

</body>
</html>
