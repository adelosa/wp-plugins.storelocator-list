<?php
/**
 * Store Locator Import/Export functionality
 * 
 * @package StoreLocator-List
 * @since 0.2.1
 */

namespace StoreLocatorList\Admin;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Import_Export {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20); // After Store Locator menu
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_sllist_export_stores', array($this, 'ajax_export_stores'));
        add_action('wp_ajax_sllist_import_stores', array($this, 'ajax_import_stores'));
        add_action('admin_init', array($this, 'check_phpspreadsheet'));
    }
    
    /**
     * Check if PhpSpreadsheet is available
     */
    public function check_phpspreadsheet() {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            add_action('admin_notices', array($this, 'phpspreadsheet_notice'));
        }
    }
    
    /**
     * Display notice if PhpSpreadsheet is not available
     */
    public function phpspreadsheet_notice() {
        if (get_current_screen()->id === 'wpsl_stores_page_sllist-import-export') {
            ?>
            <div class="notice notice-error">
                <p><strong>Store Locator Import/Export:</strong> PhpSpreadsheet library is required for Excel functionality. 
                Please install it via Composer: <code>composer require phpoffice/phpspreadsheet</code></p>
            </div>
            <?php
        }
    }
    
    /**
     * Add admin menu under Store Locator
     */
    public function add_admin_menu() {
        // Check if Store Locator plugin is active and has created its menu
        if (class_exists('WPSL_Admin')) {
            add_submenu_page(
                'edit.php?post_type=wpsl_stores',
                __('Import/Export', 'storelocator-list'),
                __('Import/Export', 'storelocator-list'),
                'manage_options',
                'sllist-import-export',
                array($this, 'admin_page')
            );
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'wpsl_stores_page_sllist-import-export') {
            return;
        }
        
        $plugin = \StoreLocatorList\SLList_Plugin::get_instance();
        
        wp_enqueue_script(
            'sllist-import-export',
            $plugin->get_plugin_url() . 'assets/js/import-export.js',
            array('jquery'),
            \StoreLocatorList\SLList_Plugin::VERSION,
            true
        );
        
        wp_localize_script('sllist-import-export', 'sllist_import_export', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sllist_import_export'),
            'strings' => array(
                'exporting' => __('Exporting...', 'storelocator-list'),
                'importing' => __('Importing...', 'storelocator-list'),
                'confirm_import' => __('This will import store data and may update existing stores. Continue?', 'storelocator-list'),
                'error' => __('An error occurred. Please try again.', 'storelocator-list')
            )
        ));
        
        wp_enqueue_style(
            'sllist-import-export',
            $plugin->get_plugin_url() . 'assets/css/import-export.css',
            array(),
            \StoreLocatorList\SLList_Plugin::VERSION
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Store Locator Import/Export', 'storelocator-list'); ?></h1>
            
            <div class="sllist-import-export-container">
                
                <!-- Export Section -->
                <div class="sllist-section sllist-export-section">
                    <div class="sllist-section-header">
                        <h2><?php echo esc_html__('Export Stores', 'storelocator-list'); ?></h2>
                        <p><?php echo esc_html__('Export all store data to an Excel file for backup or editing.', 'storelocator-list'); ?></p>
                    </div>
                    
                    <div class="sllist-section-content">
                        <form id="sllist-export-form">
                            <?php wp_nonce_field('sllist_import_export', 'sllist_export_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Export Format', 'storelocator-list'); ?></th>
                                    <td>
                                        <label>
                                            <input type="radio" name="export_format" value="xlsx" checked>
                                            <?php echo esc_html__('Excel (.xlsx)', 'storelocator-list'); ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Include Inactive Stores', 'storelocator-list'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="include_inactive" value="1">
                                            <?php echo esc_html__('Include draft and pending stores', 'storelocator-list'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php echo esc_html__('Export Stores', 'storelocator-list'); ?>
                                </button>
                            </p>
                        </form>
                        
                        <div id="export-status" class="sllist-status-message" style="display: none;"></div>
                    </div>
                </div>
                
                <!-- Import Section -->
                <div class="sllist-section sllist-import-section">
                    <div class="sllist-section-header">
                        <h2><?php echo esc_html__('Import Stores', 'storelocator-list'); ?></h2>
                        <p><?php echo esc_html__('Import store data from an Excel file. Use files exported from this system for best compatibility.', 'storelocator-list'); ?></p>
                    </div>
                    
                    <div class="sllist-section-content">
                        <form id="sllist-import-form" enctype="multipart/form-data">
                            <?php wp_nonce_field('sllist_import_export', 'sllist_import_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Import File', 'storelocator-list'); ?></th>
                                    <td>
                                        <input type="file" name="import_file" id="import_file" accept=".xlsx,.xls" required>
                                        <p class="description"><?php echo esc_html__('Select an Excel file (.xlsx or .xls) to import.', 'storelocator-list'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php echo esc_html__('Import Mode', 'storelocator-list'); ?></th>
                                    <td>
                                        <label>
                                            <input type="radio" name="import_mode" value="update" checked>
                                            <?php echo esc_html__('Update existing stores and add new ones', 'storelocator-list'); ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="import_mode" value="add_only">
                                            <?php echo esc_html__('Add new stores only (skip existing)', 'storelocator-list'); ?>
                                        </label><br>
                                        <label>
                                            <input type="radio" name="import_mode" value="replace">
                                            <?php echo esc_html__('Replace all stores (delete existing first)', 'storelocator-list'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php echo esc_html__('Import Stores', 'storelocator-list'); ?>
                                </button>
                            </p>
                        </form>
                        
                        <div id="import-status" class="sllist-status-message" style="display: none;"></div>
                        <div id="import-preview" class="sllist-import-preview" style="display: none;"></div>
                    </div>
                </div>
                
                <!-- Statistics Section -->
                <div class="sllist-section sllist-stats-section">
                    <div class="sllist-section-header">
                        <h2><?php echo esc_html__('Current Statistics', 'storelocator-list'); ?></h2>
                    </div>
                    
                    <div class="sllist-section-content">
                        <?php $this->display_store_statistics(); ?>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Display store statistics
     */
    private function display_store_statistics() {
        $stats = $this->get_store_statistics();
        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Status', 'storelocator-list'); ?></th>
                    <th><?php echo esc_html__('Count', 'storelocator-list'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo esc_html__('Published Stores', 'storelocator-list'); ?></td>
                    <td><?php echo intval($stats['publish']); ?></td>
                </tr>
                <tr>
                    <td><?php echo esc_html__('Draft Stores', 'storelocator-list'); ?></td>
                    <td><?php echo intval($stats['draft']); ?></td>
                </tr>
                <tr>
                    <td><?php echo esc_html__('Pending Stores', 'storelocator-list'); ?></td>
                    <td><?php echo intval($stats['pending']); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Total Stores', 'storelocator-list'); ?></strong></td>
                    <td><strong><?php echo intval($stats['total']); ?></strong></td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Get store statistics
     */
    private function get_store_statistics() {
        $counts = wp_count_posts('wpsl_stores');
        
        return array(
            'publish' => $counts->publish ?? 0,
            'draft' => $counts->draft ?? 0,
            'pending' => $counts->pending ?? 0,
            'total' => ($counts->publish ?? 0) + ($counts->draft ?? 0) + ($counts->pending ?? 0)
        );
    }
    
    /**
     * AJAX handler for exporting stores
     */
    public function ajax_export_stores() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sllist_import_export')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Security check failed.', 'storelocator-list')
            )));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Insufficient permissions.', 'storelocator-list')
            )));
        }
        
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('PhpSpreadsheet library is required for Excel functionality.', 'storelocator-list')
            )));
        }
        
        $include_inactive = !empty($_POST['include_inactive']);
        
        try {
            $file_info = $this->export_stores_to_excel($include_inactive);
            
            wp_die(json_encode(array(
                'success' => true,
                'data' => array(
                    'download_url' => $file_info['url'],
                    'filename' => $file_info['filename'],
                    'count' => $file_info['count']
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
     * Export stores to Excel file
     */
    private function export_stores_to_excel($include_inactive = false) {
        // Get stores
        $post_status = $include_inactive ? array('publish', 'draft', 'pending') : array('publish');
        
        $stores = get_posts(array(
            'post_type' => 'wpsl_stores',
            'post_status' => $post_status,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Create spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Store Locator Data');
        
        // Define headers
        $headers = array(
            'A1' => 'Store ID',
            'B1' => 'Store Name',
            'C1' => 'Status',
            'D1' => 'Description',
            'E1' => 'Address',
            'F1' => 'Address 2',
            'G1' => 'City',
            'H1' => 'State',
            'I1' => 'ZIP Code',
            'J1' => 'Country',
            'K1' => 'Phone',
            'L1' => 'Email',
            'M1' => 'Website',
            'N1' => 'Hours',
            'O1' => 'Latitude',
            'P1' => 'Longitude',
            'Q1' => 'Category',
            'R1' => 'Created Date',
            'S1' => 'Modified Date'
        );
        
        // Set headers
        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }
        
        // Auto-size columns
        foreach (range('A', 'S') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add data
        $row = 2;
        foreach ($stores as $store) {
            // Get all meta data
            $meta = get_post_meta($store->ID);
            
            // Get store categories
            $categories = wp_get_post_terms($store->ID, 'wpsl_store_category');
            $category_names = array();
            foreach ($categories as $category) {
                $category_names[] = $category->name;
            }
            
            $sheet->setCellValue('A' . $row, $store->ID);
            $sheet->setCellValue('B' . $row, $store->post_title);
            $sheet->setCellValue('C' . $row, $store->post_status);
            $sheet->setCellValue('D' . $row, $store->post_content);
            $sheet->setCellValue('E' . $row, $meta['wpsl_address'][0] ?? '');
            $sheet->setCellValue('F' . $row, $meta['wpsl_address2'][0] ?? '');
            $sheet->setCellValue('G' . $row, $meta['wpsl_city'][0] ?? '');
            $sheet->setCellValue('H' . $row, $meta['wpsl_state'][0] ?? '');
            $sheet->setCellValue('I' . $row, $meta['wpsl_zip'][0] ?? '');
            $sheet->setCellValue('J' . $row, $meta['wpsl_country'][0] ?? '');
            $sheet->setCellValue('K' . $row, $meta['wpsl_phone'][0] ?? '');
            $sheet->setCellValue('L' . $row, $meta['wpsl_email'][0] ?? '');
            $sheet->setCellValue('M' . $row, $meta['wpsl_url'][0] ?? '');
            $sheet->setCellValue('N' . $row, $meta['wpsl_hours'][0] ?? '');
            $sheet->setCellValue('O' . $row, $meta['wpsl_lat'][0] ?? '');
            $sheet->setCellValue('P' . $row, $meta['wpsl_lng'][0] ?? '');
            $sheet->setCellValue('Q' . $row, implode(', ', $category_names));
            $sheet->setCellValue('R' . $row, $store->post_date);
            $sheet->setCellValue('S' . $row, $store->post_modified);
            
            $row++;
        }
        
        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "store-locator-export_{$timestamp}.xlsx";
        
        // Save to uploads directory
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        // Create writer and save
        $writer = new Xlsx($spreadsheet);
        $writer->save($file_path);
        
        return array(
            'path' => $file_path,
            'url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename,
            'count' => count($stores)
        );
    }
    
    /**
     * AJAX handler for importing stores
     */
    public function ajax_import_stores() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sllist_import_export')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Security check failed.', 'storelocator-list')
            )));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Insufficient permissions.', 'storelocator-list')
            )));
        }
        
        // Check if PhpSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('PhpSpreadsheet library is required for Excel functionality.', 'storelocator-list')
            )));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('No file uploaded or upload error.', 'storelocator-list')
            )));
        }
        
        $import_mode = sanitize_text_field($_POST['import_mode'] ?? 'update');
        
        // Clear any previous debug logs
        error_log("SLList Import: Starting import process in {$import_mode} mode");
        
        try {
            $result = $this->import_stores_from_excel($_FILES['import_file'], $import_mode);
            
            wp_die(json_encode(array(
                'success' => true,
                'data' => $result
            )));
            
        } catch (Exception $e) {
            error_log("SLList Import: Error - " . $e->getMessage());
            wp_die(json_encode(array(
                'success' => false,
                'data' => $e->getMessage()
            )));
        }
    }
    
    /**
     * Import stores from Excel file
     */
    private function import_stores_from_excel($file_info, $import_mode) {
        $file_path = $file_info['tmp_name'];
        
        // Load spreadsheet
        $reader = IOFactory::createReaderForFile($file_path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Get data
        $data = $sheet->toArray();
        
        if (empty($data) || count($data) < 2) {
            throw new Exception(__('Invalid file format or no data found.', 'storelocator-list'));
        }
        
        // Validate headers
        $headers = $data[0];
        $required_headers = array('Store Name', 'Address', 'City');
        
        foreach ($required_headers as $required) {
            if (!in_array($required, $headers)) {
                throw new Exception(sprintf(__('Required column "%s" not found.', 'storelocator-list'), $required));
            }
        }
        
        // Map headers to column indices
        $header_map = array_flip($headers);
        
        // Debug: Log header mapping
        error_log("SLList Import: Headers found: " . print_r($headers, true));
        error_log("SLList Import: Header map: " . print_r($header_map, true));
        error_log("SLList Import: Store ID column index: " . ($header_map['Store ID'] ?? 'NOT FOUND'));
        
        // Delete all stores if replace mode
        if ($import_mode === 'replace') {
            $this->delete_all_stores();
        }
        
        $stats = array(
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array()
        );
        
        // Process data rows
        for ($i = 1; $i < count($data); $i++) {
            $row = $data[$i];
            
            try {
                $result = $this->process_import_row($row, $header_map, $import_mode);
                
                if ($result['action'] === 'imported') {
                    $stats['imported']++;
                } elseif ($result['action'] === 'updated') {
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
                
            } catch (Exception $e) {
                $stats['errors'][] = "Row " . ($i + 1) . ": " . $e->getMessage();
            }
        }
        
        return array(
            'stats' => $stats,
            'message' => sprintf(
                __('Import completed: %d imported, %d updated, %d skipped, %d errors', 'storelocator-list'),
                $stats['imported'],
                $stats['updated'],
                $stats['skipped'],
                count($stats['errors'])
            )
        );
    }
    
    /**
     * Process a single import row
     */
    private function process_import_row($row, $header_map, $import_mode) {
        // Extract data from row
        $store_id = 0;
        
        // Try different possible ways the Store ID column might be named
        $store_id_columns = array('Store ID', 'store_id', 'ID', 'id', 'StoreID');
        foreach ($store_id_columns as $col_name) {
            if (isset($header_map[$col_name]) && !empty($row[$header_map[$col_name]])) {
                $store_id = intval($row[$header_map[$col_name]]);
                break;
            }
        }
        
        $store_name = $row[$header_map['Store Name']] ?? '';
        
        if (empty($store_name)) {
            throw new Exception(__('Store name is required.', 'storelocator-list'));
        }
        
        // Debug: Log what we're processing
        error_log("SLList Import: Processing row - Store Name: {$store_name}, Store ID: {$store_id}");
        
        // Check if store exists
        $existing_store = false;
        if ($store_id > 0) {
            $existing_store = get_post($store_id);
            // Verify it's actually a store and not deleted
            if ($existing_store && 
                $existing_store->post_type === 'wpsl_stores' && 
                $existing_store->post_status !== 'trash') {
                // Store exists and is valid
                error_log("SLList Import: Found existing store ID {$store_id} - {$store_name}");
            } else {
                $existing_store = false;
                if ($store_id > 0) {
                    error_log("SLList Import: Store ID {$store_id} not found or invalid - {$store_name}");
                }
            }
        } else {
            error_log("SLList Import: No Store ID provided for - {$store_name}");
        }
        
        // Handle import mode logic
        if ($existing_store && $import_mode === 'add_only') {
            return array('action' => 'skipped');
        }
        
        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($store_name),
            'post_content' => sanitize_textarea_field($row[$header_map['Description']] ?? ''),
            'post_type' => 'wpsl_stores',
            'post_status' => sanitize_text_field($row[$header_map['Status']] ?? 'publish')
        );
        
        if ($existing_store) {
            $post_data['ID'] = $store_id;
            $post_id = wp_update_post($post_data);
            $action = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            $action = 'imported';
        }
        
        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }
        
        // Update meta fields
        $meta_fields = array(
            'wpsl_address' => $row[$header_map['Address']] ?? '',
            'wpsl_address2' => $row[$header_map['Address 2']] ?? '',
            'wpsl_city' => $row[$header_map['City']] ?? '',
            'wpsl_state' => $row[$header_map['State']] ?? '',
            'wpsl_zip' => $row[$header_map['ZIP Code']] ?? '',
            'wpsl_country' => $row[$header_map['Country']] ?? '',
            'wpsl_phone' => $row[$header_map['Phone']] ?? '',
            'wpsl_email' => $row[$header_map['Email']] ?? '',
            'wpsl_url' => $row[$header_map['Website']] ?? '',
            'wpsl_hours' => $row[$header_map['Hours']] ?? '',
            'wpsl_lat' => $row[$header_map['Latitude']] ?? '',
            'wpsl_lng' => $row[$header_map['Longitude']] ?? ''
        );
        
        foreach ($meta_fields as $meta_key => $meta_value) {
            // Always update meta fields, even if empty (to allow clearing values)
            update_post_meta($post_id, $meta_key, sanitize_text_field($meta_value));
        }
        
        // Handle categories
        if (!empty($header_map['Category']) && !empty($row[$header_map['Category']])) {
            $categories = explode(',', $row[$header_map['Category']]);
            $category_ids = array();
            
            foreach ($categories as $category_name) {
                $category_name = trim($category_name);
                if (!empty($category_name)) {
                    $term = get_term_by('name', $category_name, 'wpsl_store_category');
                    if (!$term) {
                        $term = wp_insert_term($category_name, 'wpsl_store_category');
                        if (!is_wp_error($term)) {
                            $category_ids[] = $term['term_id'];
                        }
                    } else {
                        $category_ids[] = $term->term_id;
                    }
                }
            }
            
            if (!empty($category_ids)) {
                wp_set_post_terms($post_id, $category_ids, 'wpsl_store_category');
            }
        }
        
        // Check if address has changed and trigger geocoding if needed
        $this->maybe_trigger_geocoding($post_id, $meta_fields, $existing_store);
        
        return array('action' => $action, 'post_id' => $post_id);
    }
    
    /**
     * Delete all stores (for replace mode)
     */
    private function delete_all_stores() {
        $stores = get_posts(array(
            'post_type' => 'wpsl_stores',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        foreach ($stores as $store_id) {
            wp_delete_post($store_id, true);
        }
    }
    
    /**
     * Check if address has changed and trigger geocoding if needed
     * Only geocodes when address data actually changes to minimize API costs
     * 
     * @param int $post_id Store post ID
     * @param array $new_meta_fields New meta field values
     * @param WP_Post|null $existing_store Existing store post (null for new stores)
     */
    private function maybe_trigger_geocoding($post_id, $new_meta_fields, $existing_store = null) {
        // Don't geocode if WP Store Locator's geocode class is not available
        global $wpsl_admin;
        if (!isset($wpsl_admin) || !isset($wpsl_admin->geocode)) {
            return;
        }
        
        // Address fields that trigger geocoding when changed
        $address_fields = array('wpsl_address', 'wpsl_address2', 'wpsl_city', 'wpsl_state', 'wpsl_zip', 'wpsl_country');
        
        $address_changed = false;
        
        if ($existing_store) {
            // For existing stores, check if any address field has changed
            foreach ($address_fields as $field) {
                $old_value = get_post_meta($post_id, $field, true);
                $new_value = $new_meta_fields[$field] ?? '';
                
                if (trim($old_value) !== trim($new_value)) {
                    $address_changed = true;
                    error_log("SLList Import: Address field '{$field}' changed for store ID {$post_id}: '{$old_value}' -> '{$new_value}'");
                    break;
                }
            }
        } else {
            // For new stores, always trigger geocoding if address data exists
            $has_address_data = false;
            foreach ($address_fields as $field) {
                if (!empty($new_meta_fields[$field])) {
                    $has_address_data = true;
                    break;
                }
            }
            $address_changed = $has_address_data;
            if ($address_changed) {
                error_log("SLList Import: New store created (ID {$post_id}), triggering geocoding");
            }
        }
        
        // Only trigger geocoding if address changed and we don't already have valid coordinates
        if ($address_changed) {
            $existing_lat = get_post_meta($post_id, 'wpsl_lat', true);
            $existing_lng = get_post_meta($post_id, 'wpsl_lng', true);
            
            // Clear existing coordinates if address changed so geocoding will trigger
            if ($existing_store && ($existing_lat || $existing_lng)) {
                error_log("SLList Import: Clearing existing coordinates for store ID {$post_id} due to address change");
                update_post_meta($post_id, 'wpsl_lat', '');
                update_post_meta($post_id, 'wpsl_lng', '');
            }
            
            // Prepare store data for geocoding (use WP Store Locator's expected format)
            $store_data = array();
            foreach ($address_fields as $field) {
                $key = str_replace('wpsl_', '', $field);
                $store_data[$key] = $new_meta_fields[$field] ?? '';
            }
            
            // Add empty lat/lng so geocoding will be triggered
            $store_data['lat'] = '';
            $store_data['lng'] = '';
            
            error_log("SLList Import: Triggering geocoding for store ID {$post_id} with data: " . json_encode($store_data));
            
            // Use WP Store Locator's geocoding system
            try {
                $wpsl_admin->geocode->check_geocode_data($post_id, $store_data);
                error_log("SLList Import: Geocoding completed for store ID {$post_id}");
            } catch (Exception $e) {
                error_log("SLList Import: Geocoding failed for store ID {$post_id}: " . $e->getMessage());
            }
        } else {
            if ($existing_store) {
                error_log("SLList Import: No address changes detected for store ID {$post_id}, skipping geocoding");
            } else {
                error_log("SLList Import: No address data provided for new store ID {$post_id}, skipping geocoding");
            }
        }
    }
}
