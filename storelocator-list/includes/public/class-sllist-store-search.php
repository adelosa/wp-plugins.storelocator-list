<?php
/**
 * Store Search functionality for public users
 * 
 * @package StoreLocator-List
 * @since 0.2.0
 */

namespace StoreLocatorList\PublicPages;

use StoreLocatorList\Core\SLList_Query_Helper;
use StoreLocatorList\Core\SLList_Security;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Store_Search {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Note: query_vars filter is now handled by the main plugin class
        
        add_action('init', array($this, 'init_store_search'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sllist_search_stores', array($this, 'ajax_search_stores'));
        add_action('wp_ajax_nopriv_sllist_search_stores', array($this, 'ajax_search_stores'));
        add_action('wp_ajax_sllist_request_store_access', array($this, 'ajax_request_store_access'));
        add_action('wp_ajax_nopriv_sllist_request_store_access', array($this, 'ajax_request_store_access'));
        
        // Handle page requests with multiple approaches
        add_action('parse_request', array($this, 'parse_request'), 1);
        add_action('template_redirect', array($this, 'handle_store_manager_page'), 5);
        add_filter('template_include', array($this, 'template_include'), 99);
        add_filter('wp_title', array($this, 'wp_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'document_title_parts'));
    }
    
    /**
     * Get the store manager page URL
     * 
     * @param bool $use_pretty_url Whether to use pretty URL or query parameter
     * @return string The store manager page URL
     */
    public static function get_store_manager_url($use_pretty_url = true) {
        if ($use_pretty_url) {
            return home_url('/store-manager/');
        } else {
            return home_url('/?sllist_page=store_manager');
        }
    }
    
    /**
     * Initialize store search functionality
     */
    public function init_store_search() {
        // Rewrite rules are now handled by the main plugin class
        // This method is kept for future initialization needs
    }
    
    /**
     * Parse request to handle our custom pages properly
     * 
     * @param WP $wp WordPress object
     */
    public function parse_request($wp) {
        // Check if this is our store manager page
        if (isset($wp->query_vars['sllist_page']) && $wp->query_vars['sllist_page'] === 'store_manager') {
            // Tell WordPress this is a valid page, not a 404
            status_header(200);
            $wp->is_404 = false;
            global $wp_query;
            $wp_query->is_404 = false;
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
        }
    }
    
    /**
     * Filter the page title for our custom pages
     * 
     * @param string $title The page title
     * @param string $sep The title separator
     * @return string Modified title
     */
    public function wp_title($title, $sep = '') {
        if (get_query_var('sllist_page') === 'store_manager') {
            return 'Store Manager ' . $sep . ' ' . get_bloginfo('name');
        }
        return $title;
    }
    
    /**
     * Filter the document title parts for our custom pages
     * 
     * @param array $title Title parts
     * @return array Modified title parts
     */
    public function document_title_parts($title) {
        if (get_query_var('sllist_page') === 'store_manager') {
            $title['title'] = 'Store Manager';
            $title['page'] = '';
            $title['tagline'] = get_bloginfo('description');
            $title['site'] = get_bloginfo('name');
        }
        return $title;
    }
    
    /**
     * Handle store manager page requests
     */
    public function handle_store_manager_page() {
        // Get query var - try both methods for compatibility
        $page = get_query_var('sllist_page');
        
        // Fallback: check $_GET directly if query_var isn't working
        if (empty($page) && isset($_GET['sllist_page'])) {
            $page = sanitize_text_field($_GET['sllist_page']);
        }
        
        // Debug logging (remove in production)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SLList Debug: template_redirect fired, sllist_page = ' . var_export($page, true));
            error_log('SLList Debug: $_GET = ' . print_r($_GET, true));
        }
        
        if ($page === 'store_manager') {
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SLList Debug: Displaying store manager page');
            }
            
            $this->display_store_manager_page();
            exit;
        }
    }
    
    /**
     * Handle template inclusion - alternative method to template_redirect
     * 
     * @param string $template The template path
     * @return string The template path or custom template
     */
    public function template_include($template) {
        // Get query var
        $page = get_query_var('sllist_page');
        
        // Also check direct URL parsing as fallback
        if (empty($page)) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#/store-manager/?$#', $request_uri)) {
                $page = 'store_manager';
            }
        }
        
        if ($page === 'store_manager') {
            // Return a custom template path that triggers our display
            $this->display_store_manager_page();
            exit;
        }
        
        return $template;
    }
    
    /**
     * Display the store manager search page
     */
    public function display_store_manager_page() {
        // Check for rate limiting
        if (SLList_Security::is_rate_limited('store_search')) {
            $this->display_rate_limited_page();
            return;
        }
        
        // Manually enqueue scripts since we're bypassing normal WordPress flow
        $this->enqueue_scripts();
        
        // Check if we have a traditional theme with header/footer or if it's a block theme
        $is_block_theme = function_exists('wp_is_block_theme') && wp_is_block_theme();
        $theme_has_header = locate_template('header.php');
        $theme_has_footer = locate_template('footer.php');
        
        if ($theme_has_header && !$is_block_theme) {
            get_header();
        } else {
            // Minimal HTML header for block themes or themes without header.php
            echo '<!DOCTYPE html>';
            echo '<html ' . get_language_attributes() . '>';
            echo '<head>';
            echo '<meta charset="' . get_bloginfo('charset') . '">';
            echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
            echo '<title>Store Manager - ' . get_bloginfo('name') . '</title>';
            wp_head();
            echo '</head>';
            echo '<body class="store-manager-page">';
        }
        
        echo '<div class="sllist-store-manager-page">';
        echo '<div class="container">';
        
        // Show access URLs for convenience (only for admins)
        if (current_user_can('manage_options')) {
            echo '<div class="sllist-admin-notice" style="background: #f0f0f1; border: 1px solid #c3c4c7; padding: 10px; margin-bottom: 20px; border-radius: 4px;">';
            echo '<p><strong>Admin Notice:</strong> This store search page can be accessed via:</p>';
            echo '<ul>';
            echo '<li><strong>Pretty URL:</strong> <code>' . home_url('/store-manager/') . '</code></li>';
            echo '<li><strong>Alternative URL:</strong> <code>' . home_url('/?sllist_page=store_manager') . '</code></li>';
            echo '</ul>';
            echo '<p><em>If the pretty URL doesn\'t work, go to Settings → Permalinks and click "Save Changes" to flush rewrite rules.</em></p>';
            echo '</div>';
        }
        
        $this->render_search_form();
        $this->render_search_results_container();
        
        echo '</div>';
        echo '</div>';
        
        if ($theme_has_footer && !$is_block_theme) {
            get_footer();
        } else {
            // Minimal HTML footer for block themes or themes without footer.php
            wp_footer();
            echo '</body>';
            echo '</html>';
        }
    }
    
    /**
     * Render the store search form
     */
    private function render_search_form() {
        ?>
        <div class="sllist-search-header">
            <h1><?php echo esc_html__('Store Manager Access', 'storelocator-list'); ?></h1>
            <p><?php echo esc_html__('Search for your store to request access to update your details.', 'storelocator-list'); ?></p>
        </div>
        
        <div class="sllist-search-form">
            <form id="sllist-store-search-form" method="post">
                <?php wp_nonce_field('sllist_store_search', 'sllist_search_nonce'); ?>
                
                <div class="sllist-form-field">
                    <label for="search_term"><?php echo esc_html__('Search for your store:', 'storelocator-list'); ?></label>
                    <input type="text" 
                           id="search_term" 
                           name="search_term" 
                           placeholder="<?php echo esc_attr__('Enter store name, email, address, or phone...', 'storelocator-list'); ?>"
                           class="sllist-search-input" 
                           required>
                </div>
                
                <div class="sllist-form-field">
                    <label><?php echo esc_html__('Search in:', 'storelocator-list'); ?></label>
                    <div class="sllist-checkbox-group">
                        <label class="sllist-checkbox-label">
                            <input type="checkbox" name="search_fields[]" value="name" checked>
                            <?php echo esc_html__('Store Name', 'storelocator-list'); ?>
                        </label>
                        <label class="sllist-checkbox-label">
                            <input type="checkbox" name="search_fields[]" value="email" checked>
                            <?php echo esc_html__('Email Address', 'storelocator-list'); ?>
                        </label>
                        <label class="sllist-checkbox-label">
                            <input type="checkbox" name="search_fields[]" value="address" checked>
                            <?php echo esc_html__('Address', 'storelocator-list'); ?>
                        </label>
                        <label class="sllist-checkbox-label">
                            <input type="checkbox" name="search_fields[]" value="phone">
                            <?php echo esc_html__('Phone Number', 'storelocator-list'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="sllist-form-field">
                    <button type="submit" class="sllist-button">
                        <?php echo esc_html__('Search Stores', 'storelocator-list'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="sllist-messages" id="sllist-search-messages"></div>
        <?php
    }
    
    /**
     * Render search results container
     */
    private function render_search_results_container() {
        ?>
        <div class="sllist-search-results" id="sllist-search-results" style="display: none;">
            <h3><?php echo esc_html__('Search Results', 'storelocator-list'); ?></h3>
            <div class="sllist-results-container" id="sllist-results-container">
                <!-- Results will be loaded here via AJAX -->
            </div>
            <div class="sllist-pagination" id="sllist-pagination">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle AJAX store search
     */
    public function ajax_search_stores() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sllist_store_search')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Security check failed. Please refresh the page and try again.', 'storelocator-list')
            )));
        }
        
        // Rate limiting check
        if (SLList_Security::is_rate_limited('store_search')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Too many search requests. Please try again later.', 'storelocator-list')
            )));
        }
        
        // Record the attempt
        SLList_Security::record_rate_limit_attempt('store_search');
        
        // Get and sanitize input
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $search_fields = array_map('sanitize_text_field', $_POST['search_fields'] ?? array('name', 'email', 'address'));
        $page = intval($_POST['page'] ?? 1);
        
        if (empty($search_term)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Please enter a search term.', 'storelocator-list')
            )));
        }
        
        // Perform search
        $search_params = array(
            'search_term' => $search_term,
            'search_fields' => $search_fields,
            'posts_per_page' => 10,
            'paged' => $page
        );
        
        $results = SLList_Query_Helper::search_stores($search_params);
        
        if (!$results->have_posts()) {
            wp_die(json_encode(array(
                'success' => true,
                'data' => array(
                    'html' => '<div class="sllist-no-results">' . 
                             esc_html__('No stores found matching your search criteria. Please try different search terms.', 'storelocator-list') . 
                             '</div>',
                    'pagination' => '',
                    'total' => 0
                )
            )));
        }
        
        // Generate results HTML
        $html = $this->generate_search_results_html($results);
        $pagination = $this->generate_pagination_html($results, $page);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'html' => $html,
                'pagination' => $pagination,
                'total' => $results->found_posts
            )
        )));
    }
    
    /**
     * Handle AJAX store access request
     */
    public function ajax_request_store_access() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sllist_store_search')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Security check failed. Please refresh the page and try again.', 'storelocator-list')
            )));
        }
        
        // Rate limiting check - more restrictive for access requests
        if (SLList_Security::is_rate_limited('access_request')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Too many access requests. Please try again later.', 'storelocator-list')
            )));
        }
        
        // Record the attempt
        SLList_Security::record_rate_limit_attempt('access_request');
        
        // Get and validate store ID
        $store_id = intval($_POST['store_id'] ?? 0);
        
        if ($store_id <= 0) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Invalid store ID.', 'storelocator-list')
            )));
        }
        
        // Verify store exists and get store data
        $store = get_post($store_id);
        if (!$store || $store->post_type !== 'wpsl_stores') {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Store not found.', 'storelocator-list')
            )));
        }
        
        // Get store email address
        $store_email = get_post_meta($store_id, 'wpsl_email', true);
        if (empty($store_email) || !is_email($store_email)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Store email address not found or invalid. Please contact the administrator.', 'storelocator-list')
            )));
        }
        
        // Check if store already has active access
        $existing_token = get_post_meta($store_id, 'sllist_access_token', true);
        $token_expires = get_post_meta($store_id, 'sllist_token_expires', true);
        $token_used = get_post_meta($store_id, 'sllist_token_used', true);
        
        if (!empty($existing_token) && !$token_used && $token_expires && time() < $token_expires) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('An active access request already exists for this store. Please check your email or wait for it to expire.', 'storelocator-list')
            )));
        }
        
        // Generate access credentials
        $credentials = SLList_Security::create_store_access_credentials($store_id);
        
        if (!$credentials) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Failed to generate access credentials. Please try again.', 'storelocator-list')
            )));
        }
        
        // Send email with access credentials
        $email_sent = $this->send_access_email($store, $credentials);
        
        if (!$email_sent) {
            // Clean up credentials if email failed
            SLList_Security::revoke_store_access($store_id);
            
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Failed to send access email. Please try again or contact the administrator.', 'storelocator-list')
            )));
        }
        
        // Success response
        wp_die(json_encode(array(
            'success' => true,
            'data' => sprintf(
                __('Access credentials have been sent to %s. Please check your email for instructions.', 'storelocator-list'),
                esc_html($store_email)
            )
        )));
    }
    
    /**
     * Send access email to store owner
     * 
     * @param \WP_Post $store Store post object
     * @param array $credentials Access credentials array
     * @return bool True if email sent successfully
     */
    private function send_access_email($store, $credentials) {
        $store_email = get_post_meta($store->ID, 'wpsl_email', true);
        $store_name = $store->post_title;
        
        // Build the access URL
        $access_url = \StoreLocatorList\PublicPages\SLList_Store_Update::get_store_update_url($credentials['token']);
        
        // Email subject
        $subject = sprintf(
            __('Store Update Access - %s', 'storelocator-list'),
            $store_name
        );
        
        // Email content
        $message = $this->get_access_email_template($store, $credentials, $access_url);
        
        // Email headers
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        return wp_mail($store_email, $subject, $message, $headers);
    }
    
    /**
     * Get access email template
     * 
     * @param \WP_Post $store Store post object
     * @param array $credentials Access credentials
     * @param string $access_url Access URL
     * @return string Email HTML content
     */
    private function get_access_email_template($store, $credentials, $access_url) {
        $store_name = esc_html($store->post_title);
        $password = esc_html($credentials['password']);
        $expires_date = date('F j, Y \a\t g:i A', $credentials['expires']);
        $site_name = get_bloginfo('name');
        
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . esc_html($subject ?? '') . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 5px; }
                .content { padding: 20px 0; }
                .credentials { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 3px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Store Update Access Request</h1>
                    <p>Access granted for: <strong>' . $store_name . '</strong></p>
                </div>
                
                <div class="content">
                    <p>Hello,</p>
                    
                    <p>You have requested access to update your store details on ' . esc_html($site_name) . '. Please use the following credentials to access your store update page:</p>
                    
                    <div class="credentials">
                        <h3>Your Access Credentials:</h3>
                        <p><strong>Password:</strong> ' . $password . '</p>
                        <p><strong>Expires:</strong> ' . $expires_date . '</p>
                    </div>
                    
                    <div class="warning">
                        <strong>Important:</strong> This access is temporary and will expire on ' . $expires_date . '. After using these credentials once, they will be deactivated for security.
                    </div>
                    
                    <p style="text-align: center;">
                        <a href="' . esc_url($access_url) . '" class="button">Update Your Store Details</a>
                    </p>
                    
                    <p>If the button above doesn\'t work, copy and paste this URL into your browser:</p>
                    <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 3px;">
                        ' . esc_url($access_url) . '
                    </p>
                    
                    <h3>What you can update:</h3>
                    <ul>
                        <li>Store name and description</li>
                        <li>Address and contact information</li>
                        <li>Phone number and email</li>
                        <li>Business hours</li>
                    </ul>
                    
                    <div class="warning">
                        <strong>Security Notice:</strong> If you did not request this access, please ignore this email. The access will expire automatically.
                    </div>
                </div>
                
                <div class="footer">
                    <p>This email was sent from ' . esc_html($site_name) . ' (' . esc_url(home_url()) . ').</p>
                    <p>For support, please contact: ' . esc_html(get_option('admin_email')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $template;
    }
    
    /**
     * Generate search results HTML
     * 
     * @param \WP_Query $results Search results
     * @return string HTML output
     */
    private function generate_search_results_html($results) {
        $html = '';
        
        while ($results->have_posts()) {
            $results->the_post();
            $store = $results->post;
            
            // Get store metadata
            $meta = new \stdClass();
            $all_meta = get_post_meta($store->ID);
            foreach ($all_meta as $key => $value) {
                $meta->$key = $value[0] ?? '';
            }
            $store->meta = $meta;
            
            $html .= $this->render_store_result_item($store);
        }
        
        wp_reset_postdata();
        
        return $html;
    }
    
    /**
     * Render a single store result item
     * 
     * @param object $store Store post object
     * @return string HTML output
     */
    private function render_store_result_item($store) {
        $store_name = esc_html($store->post_title);
        $store_id = intval($store->ID);
        
        // Build address
        $address_parts = array();
        if (!empty($store->meta->wpsl_address)) {
            $address_parts[] = esc_html($store->meta->wpsl_address);
        }
        if (!empty($store->meta->wpsl_address2)) {
            $address_parts[] = esc_html($store->meta->wpsl_address2);
        }
        
        $city_state_zip = array();
        if (!empty($store->meta->wpsl_city)) {
            $city_state_zip[] = esc_html($store->meta->wpsl_city);
        }
        if (!empty($store->meta->wpsl_state)) {
            $city_state_zip[] = esc_html($store->meta->wpsl_state);
        }
        if (!empty($store->meta->wpsl_zip)) {
            $city_state_zip[] = esc_html($store->meta->wpsl_zip);
        }
        
        if (!empty($city_state_zip)) {
            $address_parts[] = implode(' ', $city_state_zip);
        }
        
        $address = implode('<br>', $address_parts);
        
        // Check if store already has active access
        $access_status = SLList_Security::get_store_access_status($store_id);
        $has_active_access = $access_status && $access_status['is_active'];
        
        $email = !empty($store->meta->wpsl_email) ? esc_html($store->meta->wpsl_email) : '';
        $phone = !empty($store->meta->wpsl_phone) ? esc_html($store->meta->wpsl_phone) : '';
        
        ob_start();
        ?>
        <div class="sllist-store-result" data-store-id="<?php echo $store_id; ?>">
            <div class="sllist-store-info">
                <h4 class="sllist-store-name"><?php echo $store_name; ?></h4>
                
                <?php if (!empty($address)): ?>
                <div class="sllist-store-address">
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                    <?php echo $address; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($email)): ?>
                <div class="sllist-store-contact">
                    <i class="fa fa-envelope" aria-hidden="true"></i>
                    <?php echo $email; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($phone)): ?>
                <div class="sllist-store-contact">
                    <i class="fa fa-phone" aria-hidden="true"></i>
                    <?php echo $phone; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($store->post_content)): ?>
                <div class="sllist-store-description">
                    <?php echo wp_kses_post(wp_trim_words($store->post_content, 20, '...')); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="sllist-store-actions">
                <?php if ($has_active_access): ?>
                    <div class="sllist-message info">
                        <?php echo esc_html__('This store already has an active access request.', 'storelocator-list'); ?>
                    </div>
                <?php else: ?>
                    <button type="button" 
                            class="sllist-button sllist-request-access-btn" 
                            data-store-id="<?php echo $store_id; ?>"
                            data-store-name="<?php echo esc_attr($store_name); ?>">
                        <?php echo esc_html__('Request Access', 'storelocator-list'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate pagination HTML
     * 
     * @param \WP_Query $results Search results
     * @param int $current_page Current page number
     * @return string HTML output
     */
    private function generate_pagination_html($results, $current_page) {
        $total_pages = $results->max_num_pages;
        
        if ($total_pages <= 1) {
            return '';
        }
        
        $html = '<div class="sllist-pagination-wrapper">';
        
        // Previous page
        if ($current_page > 1) {
            $html .= '<button type="button" class="sllist-page-btn" data-page="' . ($current_page - 1) . '">&laquo; ' . esc_html__('Previous', 'storelocator-list') . '</button>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = ($i == $current_page) ? ' active' : '';
            $html .= '<button type="button" class="sllist-page-btn' . $active_class . '" data-page="' . $i . '">' . $i . '</button>';
        }
        
        // Next page
        if ($current_page < $total_pages) {
            $html .= '<button type="button" class="sllist-page-btn" data-page="' . ($current_page + 1) . '">' . esc_html__('Next', 'storelocator-list') . ' &raquo;</button>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Display rate limited page
     */
    private function display_rate_limited_page() {
        get_header();
        ?>
        <div class="sllist-store-manager-page">
            <div class="container">
                <div class="sllist-message error">
                    <h2><?php echo esc_html__('Too Many Requests', 'storelocator-list'); ?></h2>
                    <p><?php echo esc_html__('You have made too many search requests. Please wait a while before trying again.', 'storelocator-list'); ?></p>
                </div>
            </div>
        </div>
        <?php
        get_footer();
    }
    
    /**
     * Enqueue scripts and styles for store search
     */
    public function enqueue_scripts() {
        // Check if this is our store manager page
        $is_store_manager = false;
        
        if (get_query_var('sllist_page') === 'store_manager' || 
            (isset($_GET['sllist_page']) && $_GET['sllist_page'] === 'store_manager') ||
            preg_match('#/store-manager/?$#', $_SERVER['REQUEST_URI'] ?? '')) {
            $is_store_manager = true;
        }
        
        // Only enqueue on store manager page
        if ($is_store_manager) {
            // Get plugin instance to access URLs
            $plugin = \StoreLocatorList\SLList_Plugin::get_instance();
            
            // Enqueue jQuery if not already loaded
            if (!wp_script_is('jquery', 'enqueued')) {
                wp_enqueue_script('jquery');
            }
            
            wp_enqueue_script(
                'sllist-store-search',
                $plugin->get_plugin_url() . 'assets/js/store-search.js',
                array('jquery'),
                \StoreLocatorList\SLList_Plugin::VERSION,
                true
            );
            
            wp_localize_script('sllist-store-search', 'sllist_search_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sllist_store_search'),
                'messages' => array(
                    'searching' => __('Searching stores...', 'storelocator-list'),
                    'error' => __('An error occurred while searching. Please try again.', 'storelocator-list'),
                    'no_search_term' => __('Please enter a search term.', 'storelocator-list'),
                    'select_fields' => __('Please select at least one search field.', 'storelocator-list')
                )
            ));
            
            // Ensure our main plugin CSS is loaded
            wp_enqueue_style('sllist-styles');
        }
    }
}
