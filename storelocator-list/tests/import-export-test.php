<?php
/**
 * Import/Export Test Page
 * 
 * @package StoreLocator-List
 * @since 0.2.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Debug: Log that this file is being loaded
error_log("SLList Test: import-export-test.php file loaded");

// Only show if user is admin (remove WP_DEBUG requirement for testing)
if (!current_user_can('manage_options')) {
    error_log("SLList Test: User doesn't have manage_options capability");
    return;
}

/**
 * Add test page for import/export functionality
 */
function sllist_add_import_export_test_page() {
    // Debug: Log what's happening
    error_log("SLList Test: Attempting to add test menu");
    error_log("SLList Test: User can manage_options: " . (current_user_can('manage_options') ? 'Yes' : 'No'));
    error_log("SLList Test: WPSL_Admin exists: " . (class_exists('WPSL_Admin') ? 'Yes' : 'No'));
    
    // Check if user has permissions and WP Store Locator is active
    if (current_user_can('manage_options')) {
        if (class_exists('WPSL_Admin')) {
            error_log("SLList Test: Adding submenu under Store Locator");
            add_submenu_page(
                'edit.php?post_type=wpsl_stores',
                'Import/Export Test',
                'Import/Export Test',
                'manage_options',
                'sllist-import-export-test',
                'sllist_import_export_test_page'
            );
        } else {
            error_log("SLList Test: WPSL not found, adding as top-level menu");
            add_menu_page(
                'Store Locator Import/Export Test',
                'SL Import Test',
                'manage_options',
                'sllist-import-export-test',
                'sllist_import_export_test_page',
                'dashicons-database-import',
                30
            );
        }
    } else {
        error_log("SLList Test: Conditions not met for adding menu");
    }
}
add_action('admin_menu', 'sllist_add_import_export_test_page', 99); // Very late priority to ensure WPSL menu exists

/**
 * Test page content
 */
function sllist_import_export_test_page() {
    ?>
    <div class="wrap">
        <h1>Import/Export Test Page</h1>
        
        <div style="background: #d4edda; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb; border-radius: 4px;">
            <h3>✓ Test Page Loaded Successfully!</h3>
            <p>If you can see this page, the test menu registration is working.</p>
        </div>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
            <h2>System Requirements Check</h2>
            
            <?php
            // Check PhpSpreadsheet
            $phpspreadsheet_available = class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet');
            echo '<p><strong>PhpSpreadsheet:</strong> ';
            if ($phpspreadsheet_available) {
                echo '<span style="color: green;">✓ Available</span>';
            } else {
                echo '<span style="color: red;">✗ Not Available - Run "composer install" in plugin directory</span>';
            }
            echo '</p>';
            
            // Check file permissions
            $upload_dir = wp_upload_dir();
            $uploads_writable = is_writable($upload_dir['path']);
            echo '<p><strong>Uploads Directory:</strong> ';
            if ($uploads_writable) {
                echo '<span style="color: green;">✓ Writable (' . $upload_dir['path'] . ')</span>';
            } else {
                echo '<span style="color: red;">✗ Not Writable (' . $upload_dir['path'] . ')</span>';
            }
            echo '</p>';
            
            // Check if Store Locator is active
            $wpsl_active = class_exists('WPSL_Admin');
            echo '<p><strong>WP Store Locator:</strong> ';
            if ($wpsl_active) {
                echo '<span style="color: green;">✓ Active</span>';
            } else {
                echo '<span style="color: orange;">⚠ Not Active - Import/Export menu may not appear</span>';
            }
            echo '</p>';
            
            // Check for stores
            $store_count = wp_count_posts('wpsl_stores');
            $total_stores = ($store_count->publish ?? 0) + ($store_count->draft ?? 0) + ($store_count->pending ?? 0);
            echo '<p><strong>Total Stores:</strong> ' . $total_stores . '</p>';
            ?>
        </div>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
            <h2>Test Actions</h2>
            
            <?php if ($phpspreadsheet_available): ?>
                <p><strong>Export Test:</strong></p>
                <p>Visit the <a href="<?php echo admin_url('edit.php?post_type=wpsl_stores&page=sllist-import-export'); ?>">Import/Export page</a> to test export functionality.</p>
                
                <p><strong>Sample Data Creation:</strong></p>
                <form method="post" action="">
                    <?php wp_nonce_field('sllist_create_sample_data', 'sample_data_nonce'); ?>
                    <input type="hidden" name="action" value="create_sample_data">
                    <p>
                        <input type="submit" class="button button-secondary" value="Create 5 Sample Stores" onclick="return confirm('This will create 5 sample stores for testing. Continue?');">
                    </p>
                </form>
                
                <p><strong>Debug Information:</strong></p>
                <form method="post" action="">
                    <?php wp_nonce_field('sllist_show_debug_log', 'debug_log_nonce'); ?>
                    <input type="hidden" name="action" value="show_debug_log">
                    <p>
                        <input type="submit" class="button button-secondary" value="Show Recent Import Debug Logs">
                    </p>
                </form>
            <?php else: ?>
                <p style="color: red;">PhpSpreadsheet is required for testing. Please run "composer install" in the plugin directory.</p>
            <?php endif; ?>
        </div>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
            <h2>Geocoding Integration Test</h2>
            
            <?php 
            // Check if WP Store Locator geocoding is available
            global $wpsl_admin;
            $geocoding_available = isset($wpsl_admin) && isset($wpsl_admin->geocode);
            ?>
            
            <p><strong>Geocoding Status:</strong> 
            <?php if ($geocoding_available): ?>
                <span style="color: green;">✓ WP Store Locator geocoding available</span>
            <?php else: ?>
                <span style="color: red;">✗ WP Store Locator geocoding not available</span>
            <?php endif; ?>
            </p>
            
            <?php if ($geocoding_available): ?>
                <p><strong>How it works:</strong></p>
                <ul style="margin-left: 20px;">
                    <li>During import, our system detects when address fields change</li>
                    <li>Geocoding only triggers when address data actually changes (cost optimization)</li>
                    <li>New stores automatically get geocoded if they have address data</li>
                    <li>Existing stores only get geocoded if their address changes</li>
                    <li>Uses WP Store Locator's existing Google Maps API integration</li>
                </ul>
                
                <p><strong>Test the integration:</strong></p>
                <ol style="margin-left: 20px;">
                    <li>Create sample stores using the button above</li>
                    <li>Export them to Excel</li>
                    <li>Modify some address fields in the Excel file</li>
                    <li>Import the modified file</li>
                    <li>Check the debug logs to see geocoding activity</li>
                </ol>
                
                <p><strong>What to look for in debug logs:</strong></p>
                <ul style="margin-left: 20px;">
                    <li>"Address field changed" - indicates address change detection</li>
                    <li>"Triggering geocoding" - confirms geocoding API call</li>
                    <li>"Geocoding completed" - successful coordinate retrieval</li>
                    <li>"No address changes detected" - cost optimization in action</li>
                </ul>
            <?php else: ?>
                <p style="color: orange;">Enable WP Store Locator plugin to test geocoding integration.</p>
            <?php endif; ?>
        </div>
        
        <div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">
            <h2>Debug Information</h2>
            <pre style="background: #f5f5f5; padding: 10px; overflow: auto;">
PHP Version: <?php echo PHP_VERSION; ?>

WordPress Version: <?php echo get_bloginfo('version'); ?>

Plugin Version: <?php echo \StoreLocatorList\SLList_Plugin::VERSION; ?>

Upload Max Size: <?php echo ini_get('upload_max_filesize'); ?>

Post Max Size: <?php echo ini_get('post_max_size'); ?>

Memory Limit: <?php echo ini_get('memory_limit'); ?>

Max Execution Time: <?php echo ini_get('max_execution_time'); ?>s
            </pre>
        </div>
    </div>
    
    <?php
    // Handle sample data creation
    if (isset($_POST['action']) && $_POST['action'] === 'create_sample_data') {
        if (wp_verify_nonce($_POST['sample_data_nonce'], 'sllist_create_sample_data')) {
            sllist_create_sample_stores();
            echo '<div class="notice notice-success"><p>Sample stores created successfully!</p></div>';
        }
    }
    
    // Handle debug log display
    if (isset($_POST['action']) && $_POST['action'] === 'show_debug_log') {
        if (wp_verify_nonce($_POST['debug_log_nonce'], 'sllist_show_debug_log')) {
            echo '<div style="background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 4px;">';
            echo '<h3>Recent Import Debug Logs</h3>';
            sllist_show_debug_logs();
            echo '</div>';
        }
    }
}

/**
 * Create sample stores for testing
 */
function sllist_create_sample_stores() {
    $sample_stores = array(
        array(
            'name' => 'Downtown Coffee Shop',
            'address' => '123 Main Street',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'phone' => '(555) 123-4567',
            'email' => 'info@downtowncoffee.com'
        ),
        array(
            'name' => 'Westside Electronics',
            'address' => '456 West Avenue',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90210',
            'phone' => '(555) 987-6543',
            'email' => 'sales@westsideelectronics.com'
        ),
        array(
            'name' => 'Central Bookstore',
            'address' => '789 Central Park',
            'city' => 'Chicago',
            'state' => 'IL',
            'zip' => '60601',
            'phone' => '(555) 555-0123',
            'email' => 'books@centralstore.com'
        ),
        array(
            'name' => 'Northside Pharmacy',
            'address' => '321 North Road',
            'city' => 'Seattle',
            'state' => 'WA',
            'zip' => '98101',
            'phone' => '(555) 111-2222',
            'email' => 'prescriptions@northsidepharmacy.com'
        ),
        array(
            'name' => 'South Beach Restaurant',
            'address' => '654 Ocean Drive',
            'city' => 'Miami',
            'state' => 'FL',
            'zip' => '33139',
            'phone' => '(555) 333-4444',
            'email' => 'reservations@southbeachrestaurant.com'
        )
    );
    
    foreach ($sample_stores as $store_data) {
        // Create the post
        $post_id = wp_insert_post(array(
            'post_title' => $store_data['name'],
            'post_content' => 'Sample store created for import/export testing.',
            'post_type' => 'wpsl_stores',
            'post_status' => 'publish'
        ));
        
        if ($post_id && !is_wp_error($post_id)) {
            // Add meta data
            update_post_meta($post_id, 'wpsl_address', $store_data['address']);
            update_post_meta($post_id, 'wpsl_city', $store_data['city']);
            update_post_meta($post_id, 'wpsl_state', $store_data['state']);
            update_post_meta($post_id, 'wpsl_zip', $store_data['zip']);
            update_post_meta($post_id, 'wpsl_phone', $store_data['phone']);
            update_post_meta($post_id, 'wpsl_email', $store_data['email']);
            update_post_meta($post_id, 'wpsl_country', 'United States');
            
            // Add some sample coordinates (approximate)
            $coordinates = array(
                'New York' => array('lat' => '40.7128', 'lng' => '-74.0060'),
                'Los Angeles' => array('lat' => '34.0522', 'lng' => '-118.2437'),
                'Chicago' => array('lat' => '41.8781', 'lng' => '-87.6298'),
                'Seattle' => array('lat' => '47.6062', 'lng' => '-122.3321'),
                'Miami' => array('lat' => '25.7617', 'lng' => '-80.1918')
            );
            
            if (isset($coordinates[$store_data['city']])) {
                update_post_meta($post_id, 'wpsl_lat', $coordinates[$store_data['city']]['lat']);
                update_post_meta($post_id, 'wpsl_lng', $coordinates[$store_data['city']]['lng']);
            }
        }
    }
}

/**
 * Show recent debug logs related to import/export
 */
function sllist_show_debug_logs() {
    // Try to read error log if it exists
    $log_file = ini_get('error_log');
    
    if (!$log_file || !file_exists($log_file)) {
        echo '<p>No debug log file found or accessible.</p>';
        echo '<p>Error log location: ' . ($log_file ?: 'Not configured') . '</p>';
        return;
    }
    
    if (!is_readable($log_file)) {
        echo '<p>Debug log file is not readable.</p>';
        return;
    }
    
    // Read last 100 lines of the log file
    $lines = array();
    $file = new SplFileObject($log_file, 'r');
    $file->seek(PHP_INT_MAX);
    $last_line = $file->key();
    $lines_to_read = min(100, $last_line);
    
    $start_line = max(0, $last_line - $lines_to_read);
    $file->seek($start_line);
    
    $import_logs = array();
    while (!$file->eof()) {
        $line = $file->current();
        if (strpos($line, 'SLList Import:') !== false) {
            $import_logs[] = $line;
        }
        $file->next();
    }
    
    if (empty($import_logs)) {
        echo '<p>No recent import debug logs found.</p>';
        echo '<p>Try running an import operation and then check again.</p>';
    } else {
        echo '<pre style="background: #fff; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto; font-size: 12px;">';
        foreach (array_slice($import_logs, -20) as $log_line) {
            echo esc_html($log_line);
        }
        echo '</pre>';
    }
}
