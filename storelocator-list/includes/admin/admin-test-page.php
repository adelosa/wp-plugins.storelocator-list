<?php
/**
 * WordPress Admin Test Page for Store Update functionality
 * 
 * This creates an admin page accessible via WordPress admin to test the store update functionality.
 * Access via: WordPress Admin > Tools > Store Update Test
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Store_Update_Test {
    
    /**
     * Constructor - Hook into WordPress
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_sllist_test_store_update', array($this, 'ajax_test_store_update'));
        add_action('wp_ajax_sllist_flush_rewrite_rules', array($this, 'ajax_flush_rewrite_rules'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_management_page(
            'Store Update Test',
            'Store Update Test',
            'manage_options',
            'sllist-store-update-test',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'tools_page_sllist-store-update-test') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'sllist_test', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sllist_test_nonce')
        ));
    }
    
    /**
     * Display admin test page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Store Update Functionality Test</h1>
            <p>This page tests the store update functionality implementation.</p>
            
            <div id="test-results">
                <!-- Results will be populated here -->
            </div>
            
            <div class="test-section">
                <h2>Quick Tests</h2>
                <p>
                    <button type="button" class="button button-primary" onclick="runAllTests()">Run All Tests</button>
                    <button type="button" class="button" onclick="flushRewriteRules()">Flush Rewrite Rules</button>
                    <button type="button" class="button" onclick="testUrls()">Test URLs</button>
                </p>
            </div>
            
            <div class="test-section">
                <h2>Manual Test Links</h2>
                <p><strong>Test these URLs in a new tab/window:</strong></p>
                <ul>
                    <li><a href="<?php echo home_url('/store-manager/'); ?>" target="_blank">Store Manager Page</a></li>
                    <li><a href="<?php echo home_url('/update-store/'); ?>" target="_blank">Update Store Page (no token)</a></li>
                    <li><a href="<?php echo home_url('/?sllist_page=store_manager'); ?>" target="_blank">Store Manager (fallback URL)</a></li>
                    <li><a href="<?php echo home_url('/?sllist_page=store_update'); ?>" target="_blank">Update Store (fallback URL)</a></li>
                </ul>
            </div>
            
            <div class="test-section">
                <h2>Test Store Creation</h2>
                <div id="test-store-section">
                    <p>
                        <button type="button" class="button" onclick="createTestStore()">Create Test Store</button>
                        <button type="button" class="button" onclick="generateTestAccess()">Generate Test Access</button>
                    </p>
                    <div id="test-store-results"></div>
                </div>
            </div>
            
            <div class="test-section">
                <h2>Live Test Console</h2>
                <div id="test-console" style="background: #f1f1f1; padding: 15px; border-radius: 4px; font-family: monospace; height: 300px; overflow-y: scroll;">
                    <div class="console-line">Ready for testing...</div>
                </div>
            </div>
        </div>
        
        <style>
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }
        .console-line {
            margin: 2px 0;
            padding: 2px 0;
        }
        .console-success {
            color: #46b450;
        }
        .console-error {
            color: #dc3232;
        }
        .console-warning {
            color: #ffb900;
        }
        .console-info {
            color: #0073aa;
        }
        </style>
        
        <script>
        let testConsole = document.getElementById('test-console');
        
        function log(message, type = 'info') {
            let line = document.createElement('div');
            line.className = 'console-line console-' + type;
            line.innerHTML = new Date().toLocaleTimeString() + ' - ' + message;
            testConsole.appendChild(line);
            testConsole.scrollTop = testConsole.scrollHeight;
        }
        
        function runAllTests() {
            log('Starting comprehensive test suite...', 'info');
            testClassLoading();
            testAssetFiles();
            testRewriteRules();
            testSecurityFunctions();
        }
        
        function testClassLoading() {
            log('Testing class loading...', 'info');
            
            // Test if classes exist
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_test_store_update',
                test_type: 'class_loading',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ All classes loaded successfully', 'success');
                    if (response.data.details) {
                        response.data.details.forEach(function(detail) {
                            log('  - ' + detail, 'success');
                        });
                    }
                } else {
                    log('✗ Class loading failed: ' + response.data, 'error');
                }
            }).fail(function() {
                log('✗ AJAX request failed for class loading test', 'error');
            });
        }
        
        function testAssetFiles() {
            log('Testing asset files...', 'info');
            
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_test_store_update',
                test_type: 'asset_files',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ Asset files check completed', 'success');
                    response.data.forEach(function(result) {
                        log('  ' + result.file + ': ' + (result.exists ? '✓ Found' : '✗ Missing'), result.exists ? 'success' : 'error');
                    });
                } else {
                    log('✗ Asset files test failed: ' + response.data, 'error');
                }
            });
        }
        
        function testRewriteRules() {
            log('Testing rewrite rules...', 'info');
            
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_test_store_update',
                test_type: 'rewrite_rules',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ Rewrite rules found: ' + response.data.found_rules.length, 'success');
                    response.data.found_rules.forEach(function(rule) {
                        log('  - ' + rule.pattern + ' → ' + rule.replacement, 'info');
                    });
                    
                    if (response.data.recommendations.length > 0) {
                        response.data.recommendations.forEach(function(rec) {
                            log('  ⚠ ' + rec, 'warning');
                        });
                    }
                } else {
                    log('✗ Rewrite rules test failed: ' + response.data, 'error');
                }
            });
        }
        
        function testSecurityFunctions() {
            log('Testing security functions...', 'info');
            
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_test_store_update',
                test_type: 'security_functions',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ Security functions working', 'success');
                    log('  - Token: ' + response.data.token.substring(0, 20) + '...', 'info');
                    log('  - Password: ' + response.data.password, 'info');
                    log('  - Hash verification: ' + (response.data.hash_valid ? '✓ Valid' : '✗ Invalid'), response.data.hash_valid ? 'success' : 'error');
                } else {
                    log('✗ Security functions test failed: ' + response.data, 'error');
                }
            });
        }
        
        function flushRewriteRules() {
            log('Flushing rewrite rules...', 'info');
            
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_flush_rewrite_rules',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ Rewrite rules flushed successfully', 'success');
                    log('  You should now test the pretty URLs', 'info');
                } else {
                    log('✗ Failed to flush rewrite rules: ' + response.data, 'error');
                }
            }).fail(function() {
                log('✗ AJAX request failed for flush rewrite rules', 'error');
            });
        }
        
        function testUrls() {
            log('Testing URL generation...', 'info');
            
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_test_store_update',
                test_type: 'url_generation',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ URL generation working', 'success');
                    response.data.urls.forEach(function(url) {
                        log('  - ' + url.name + ': ' + url.url, 'info');
                    });
                } else {
                    log('✗ URL generation test failed: ' + response.data, 'error');
                }
            });
        }
        
        function createTestStore() {
            log('Creating test store...', 'info');
            
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_test_store_update',
                test_type: 'create_test_store',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ Test store created: ' + response.data.store_name + ' (ID: ' + response.data.store_id + ')', 'success');
                    document.getElementById('test-store-results').innerHTML = 
                        '<p><strong>Test Store Created:</strong><br>' +
                        'Name: ' + response.data.store_name + '<br>' +
                        'ID: ' + response.data.store_id + '<br>' +
                        'Email: ' + response.data.store_email + '</p>';
                } else {
                    log('✗ Failed to create test store: ' + response.data, 'error');
                }
            });
        }
        
        function generateTestAccess() {
            log('Generating test access credentials...', 'info');
            
            jQuery.post(sllist_test.ajax_url, {
                action: 'sllist_test_store_update',
                test_type: 'generate_test_access',
                nonce: sllist_test.nonce
            }, function(response) {
                if (response.success) {
                    log('✓ Test access generated', 'success');
                    log('  - Token: ' + response.data.token, 'info');
                    log('  - Password: ' + response.data.password, 'info');
                    log('  - Test URL: ' + response.data.test_url, 'info');
                    
                    document.getElementById('test-store-results').innerHTML += 
                        '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px;">' +
                        '<h4>Test Access Credentials</h4>' +
                        '<p><strong>Password:</strong> <code>' + response.data.password + '</code></p>' +
                        '<p><strong>Test URL:</strong><br><a href="' + response.data.test_url + '" target="_blank">' + response.data.test_url + '</a></p>' +
                        '<p><em>Click the URL above and use the password to test the update functionality.</em></p>' +
                        '</div>';
                } else {
                    log('✗ Failed to generate test access: ' + response.data, 'error');
                }
            });
        }
        </script>
        <?php
    }
    
    /**
     * AJAX handler for various tests
     */
    public function ajax_test_store_update() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sllist_test_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Invalid nonce')));
        }
        
        // Check admin capability
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Insufficient permissions')));
        }
        
        $test_type = sanitize_text_field($_POST['test_type'] ?? '');
        
        switch ($test_type) {
            case 'class_loading':
                $this->test_class_loading();
                break;
            case 'asset_files':
                $this->test_asset_files();
                break;
            case 'rewrite_rules':
                $this->test_rewrite_rules();
                break;
            case 'security_functions':
                $this->test_security_functions();
                break;
            case 'url_generation':
                $this->test_url_generation();
                break;
            case 'create_test_store':
                $this->create_test_store();
                break;
            case 'generate_test_access':
                $this->generate_test_access();
                break;
            default:
                wp_die(json_encode(array('success' => false, 'data' => 'Invalid test type')));
        }
    }
    
    /**
     * Test class loading
     */
    private function test_class_loading() {
        $classes_to_test = array(
            'StoreLocatorList\PublicPages\SLList_Store_Update',
            'StoreLocatorList\PublicPages\SLList_Store_Search',
            'StoreLocatorList\Core\SLList_Security',
            'StoreLocatorList\SLList_Plugin'
        );
        
        $details = array();
        $all_loaded = true;
        
        foreach ($classes_to_test as $class) {
            if (class_exists($class)) {
                $details[] = "✓ {$class}";
            } else {
                $details[] = "✗ {$class}";
                $all_loaded = false;
            }
        }
        
        wp_die(json_encode(array(
            'success' => $all_loaded,
            'data' => array('details' => $details)
        )));
    }
    
    /**
     * Test asset files
     */
    private function test_asset_files() {
        $plugin_path = plugin_dir_path(dirname(__FILE__, 2));
        
        $files_to_test = array(
            'storelocator-list/assets/css/store-update.css',
            'storelocator-list/assets/js/store-update.js',
            'storelocator-list/includes/public/class-sllist-store-update.php'
        );
        
        $results = array();
        
        foreach ($files_to_test as $file) {
            $full_path = $plugin_path . $file;
            $results[] = array(
                'file' => $file,
                'exists' => file_exists($full_path),
                'path' => $full_path
            );
        }
        
        wp_die(json_encode(array('success' => true, 'data' => $results)));
    }
    
    /**
     * Test rewrite rules
     */
    private function test_rewrite_rules() {
        $rewrite_rules = get_option('rewrite_rules', array());
        $found_rules = array();
        $recommendations = array();
        
        foreach ($rewrite_rules as $pattern => $replacement) {
            if (strpos($pattern, 'store-manager') !== false || strpos($pattern, 'update-store') !== false) {
                $found_rules[] = array(
                    'pattern' => $pattern,
                    'replacement' => $replacement
                );
            }
        }
        
        if (empty($found_rules)) {
            $recommendations[] = 'No store-related rewrite rules found. Try flushing rewrite rules.';
        }
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'found_rules' => $found_rules,
                'recommendations' => $recommendations
            )
        )));
    }
    
    /**
     * Test security functions
     */
    private function test_security_functions() {
        try {
            if (!class_exists('StoreLocatorList\Core\SLList_Security')) {
                throw new Exception('Security class not found');
            }
            
            $token = \StoreLocatorList\Core\SLList_Security::generate_access_token();
            $password = \StoreLocatorList\Core\SLList_Security::generate_access_password();
            $hash = \StoreLocatorList\Core\SLList_Security::hash_password($password);
            $hash_valid = \StoreLocatorList\Core\SLList_Security::verify_password($password, $hash);
            
            wp_die(json_encode(array(
                'success' => true,
                'data' => array(
                    'token' => $token,
                    'password' => $password,
                    'hash_valid' => $hash_valid
                )
            )));
        } catch (Exception $e) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => $e->getMessage()
            )));
        }
    }
    
    /**
     * Test URL generation
     */
    private function test_url_generation() {
        try {
            $test_token = 'test-token-12345';
            
            $urls = array(
                array(
                    'name' => 'Store Manager (pretty)',
                    'url' => \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url(true)
                ),
                array(
                    'name' => 'Store Manager (fallback)',
                    'url' => \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url(false)
                ),
                array(
                    'name' => 'Store Update (pretty)',
                    'url' => \StoreLocatorList\PublicPages\SLList_Store_Update::get_store_update_url($test_token, true)
                ),
                array(
                    'name' => 'Store Update (fallback)',
                    'url' => \StoreLocatorList\PublicPages\SLList_Store_Update::get_store_update_url($test_token, false)
                )
            );
            
            wp_die(json_encode(array(
                'success' => true,
                'data' => array('urls' => $urls)
            )));
        } catch (Exception $e) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => $e->getMessage()
            )));
        }
    }
    
    /**
     * Create test store
     */
    private function create_test_store() {
        $store_data = array(
            'post_title' => 'Test Store - ' . date('Y-m-d H:i:s'),
            'post_type' => 'wpsl_stores',
            'post_status' => 'publish',
            'post_content' => 'This is a test store created for testing the store update functionality.'
        );
        
        $store_id = wp_insert_post($store_data);
        
        if (is_wp_error($store_id)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => $store_id->get_error_message()
            )));
        }
        
        // Add meta data
        $test_email = 'test-' . time() . '@example.com';
        update_post_meta($store_id, 'wpsl_email', $test_email);
        update_post_meta($store_id, 'wpsl_address', '123 Test Street');
        update_post_meta($store_id, 'wpsl_city', 'Test City');
        update_post_meta($store_id, 'wpsl_state', 'Test State');
        update_post_meta($store_id, 'wpsl_zip', '12345');
        update_post_meta($store_id, 'wpsl_phone', '(555) 123-4567');
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'store_id' => $store_id,
                'store_name' => $store_data['post_title'],
                'store_email' => $test_email
            )
        )));
    }
    
    /**
     * Generate test access
     */
    private function generate_test_access() {
        // Find a test store
        $stores = get_posts(array(
            'post_type' => 'wpsl_stores',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'wpsl_email',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (empty($stores)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'No stores found. Create a test store first.'
            )));
        }
        
        $store = $stores[0];
        $credentials = \StoreLocatorList\Core\SLList_Security::create_store_access_credentials($store->ID);
        
        if (!$credentials) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Failed to create access credentials'
            )));
        }
        
        $test_url = \StoreLocatorList\PublicPages\SLList_Store_Update::get_store_update_url($credentials['token']);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'token' => $credentials['token'],
                'password' => $credentials['password'],
                'test_url' => $test_url,
                'store_name' => $store->post_title
            )
        )));
    }
    
    /**
     * AJAX handler for flushing rewrite rules
     */
    public function ajax_flush_rewrite_rules() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sllist_test_nonce')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Invalid nonce')));
        }
        
        // Check admin capability
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'data' => 'Insufficient permissions')));
        }
        
        // Flush rewrite rules
        flush_rewrite_rules(true);
        
        wp_die(json_encode(array('success' => true, 'data' => 'Rewrite rules flushed')));
    }
}

// Initialize the test page if we're in admin
if (is_admin()) {
    new SLList_Store_Update_Test();
    
    // Add debug notice to confirm loading
    add_action('admin_notices', function() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Store Update Test:</strong> Admin test page loaded successfully! Check Tools menu.</p>';
            echo '</div>';
        }
    });
}
?>
