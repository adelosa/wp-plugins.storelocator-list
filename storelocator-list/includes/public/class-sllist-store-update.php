<?php
/**
 * Store Update functionality for public users
 * 
 * @package StoreLocator-List
 * @since 0.2.0
 */

namespace StoreLocatorList\PublicPages;

use StoreLocatorList\Core\SLList_Security;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Store_Update {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Note: query_vars filter is now handled by the main plugin class
        
        add_action('init', array($this, 'init_store_update'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sllist_authenticate_store_access', array($this, 'ajax_authenticate_store_access'));
        add_action('wp_ajax_nopriv_sllist_authenticate_store_access', array($this, 'ajax_authenticate_store_access'));
        add_action('wp_ajax_sllist_update_store_details', array($this, 'ajax_update_store_details'));
        add_action('wp_ajax_nopriv_sllist_update_store_details', array($this, 'ajax_update_store_details'));
        
        // Handle page requests with multiple approaches
        add_action('parse_request', array($this, 'parse_request'), 1);
        add_action('template_redirect', array($this, 'handle_store_update_page'), 5);
        add_filter('template_include', array($this, 'template_include'), 99);
        add_filter('wp_title', array($this, 'wp_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'document_title_parts'));
    }
    
    /**
     * Get the store update page URL
     * 
     * @param string $token Access token (optional)
     * @param bool $use_pretty_url Whether to use pretty URL or query parameter
     * @return string The store update page URL
     */
    public static function get_store_update_url($token = '', $use_pretty_url = true) {
        if ($use_pretty_url) {
            $permalink = \StoreLocatorList\Admin\SLList_Settings::get_setting('update_permalink', 'update-store');
            $url = home_url('/' . $permalink . '/');
            if (!empty($token)) {
                $url .= '?token=' . urlencode($token);
            }
            return $url;
        } else {
            $url = home_url('/?sllist_page=store_update');
            if (!empty($token)) {
                $url .= '&token=' . urlencode($token);
            }
            return $url;
        }
    }
    
    /**
     * Initialize store update functionality
     */
    public function init_store_update() {
        // Rewrite rules are now handled by the main plugin class
        // This method is kept for future initialization needs
    }
    
    /**
     * Parse request to handle our custom pages properly
     * 
     * @param WP $wp WordPress object
     */
    public function parse_request($wp) {
        // Check if this is our store update page
        if (isset($wp->query_vars['sllist_page']) && $wp->query_vars['sllist_page'] === 'store_update') {
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
        if (get_query_var('sllist_page') === 'store_update') {
            return 'Update Store Details ' . $sep . ' ' . get_bloginfo('name');
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
        if (get_query_var('sllist_page') === 'store_update') {
            // Check if this is the success page
            if (isset($_GET['success']) && $_GET['success'] === '1') {
                $title['title'] = 'Update Successful';
            } else {
                $title['title'] = 'Update Store Details';
            }
            $title['page'] = '';
            $title['tagline'] = get_bloginfo('description');
            $title['site'] = get_bloginfo('name');
        }
        return $title;
    }
    
    /**
     * Handle store update page requests
     */
    public function handle_store_update_page() {
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
        
        if ($page === 'store_update') {
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SLList Debug: Displaying store update page');
            }

            // Check if this is a success page request
            if (isset($_GET['success']) && $_GET['success'] === '1') {
                $this->display_success_page();
            } else {
                $this->display_store_update_page();
            }
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
            if (preg_match('#/update-store/?(\?.*)?$#', $request_uri)) {
                $page = 'store_update';
            }
        }
        
        if ($page === 'store_update') {
            // Return a custom template path that triggers our display
            $this->display_store_update_page();
            exit;
        }
        
        return $template;
    }
    
    /**
     * Display the store update page
     */
    public function display_store_update_page() {
        // Get token from URL parameter
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
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
            echo '<title>Update Store Details - ' . get_bloginfo('name') . '</title>';
            wp_head();
            echo '</head>';
            echo '<body class="store-update-page">';
        }
        
        echo '<div class="sllist-store-update-page">';
        echo '<div class="container">';
        
        // Show access URLs for convenience (only for admins)
        if (current_user_can('manage_options')) {
            $update_permalink = \StoreLocatorList\Admin\SLList_Settings::get_setting('update_permalink', 'update-store');
            echo '<div class="sllist-admin-notice" style="background: #f0f0f1; border: 1px solid #c3c4c7; padding: 10px; margin-bottom: 20px; border-radius: 4px;">';
            echo '<p><strong>Admin Notice:</strong> This store update page can be accessed via:</p>';
            echo '<ul>';
            echo '<li><strong>Pretty URL:</strong> <code>' . home_url('/' . $update_permalink . '/') . '</code></li>';
            echo '<li><strong>Alternative URL:</strong> <code>' . home_url('/?sllist_page=store_update') . '</code></li>';
            echo '</ul>';
            echo '<p><em>If the pretty URL doesn\'t work, go to Settings → Permalinks and click "Save Changes" to flush rewrite rules.</em></p>';
            echo '</div>';
        }
        
        if (empty($token)) {
            $this->display_no_token_page();
        } else {
            $this->display_authentication_page($token);
        }
        
        echo '</div>'; // .container
        echo '</div>'; // .sllist-store-update-page
        
        if ($theme_has_footer && !$is_block_theme) {
            get_footer();
        } else {
            wp_footer();
            echo '</body>';
            echo '</html>';
        }
    }

    /**
     * Display the success page after store update
     */
    public function display_success_page() {
        // Get store name from URL parameter
        $store_name = isset($_GET['store_name']) ? sanitize_text_field(urldecode($_GET['store_name'])) : __('Your Store', 'storelocator-list');

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
            echo '<title>Update Successful - ' . get_bloginfo('name') . '</title>';
            wp_head();
            echo '</head>';
            echo '<body class="store-update-success-page">';
        }

        echo '<div class="sllist-store-update-success">';
        echo '<div class="container">';

        // Success content
        echo '<div class="success-content" style="text-align: center; max-width: 600px; margin: 50px auto; padding: 40px 20px;">';
        
        // Success icon
        echo '<div class="success-icon" style="font-size: 60px; color: #28a745; margin-bottom: 20px;">✓</div>';
        
        // Main heading
        $success_title = \StoreLocatorList\Admin\SLList_Settings::get_setting('update_success_title', 'Update Successful!');
        echo '<h1 style="color: #28a745; margin-bottom: 20px;">' . esc_html($success_title) . '</h1>';
        
        // Store name
        echo '<h2 style="margin-bottom: 30px; color: #333;">' . esc_html($store_name) . '</h2>';
        
        // Success message
        $success_message = \StoreLocatorList\Admin\SLList_Settings::get_setting('update_success_message', 'Your store listing has been updated successfully! Your changes have been saved and are now live on the website. You will also receive a confirmation email shortly.');
        echo '<div class="success-message" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 30px; margin-bottom: 30px; color: #155724;">';
        echo '<p style="font-size: 18px; margin-bottom: 15px;"><strong>' . esc_html($success_message) . '</strong></p>';
        echo '</div>';

        // Security notice
        echo '<div class="security-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-bottom: 30px; color: #856404;">';
        echo '<p><strong>' . __('Security Notice:', 'storelocator-list') . '</strong> ' . __('For security purposes, your access token has been automatically deactivated. If you need to make additional changes in the future, please request new access from the store manager page.', 'storelocator-list') . '</p>';
        echo '</div>';

        // Action buttons
        echo '<div class="action-buttons" style="margin-top: 40px;">';
        $store_manager_url = \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url();
        echo '<a href="' . home_url() . '" class="button button-primary button-large" style="background: #0073aa; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin-right: 15px;">' . __('Return to Homepage', 'storelocator-list') . '</a>';
        echo '<a href="' . $store_manager_url . '" class="button button-secondary button-large" style="background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;">' . __('Request New Access', 'storelocator-list') . '</a>';
        echo '</div>';

        echo '</div>'; // .success-content
        echo '</div>'; // .container
        echo '</div>'; // .sllist-store-update-success

        if ($theme_has_footer && !$is_block_theme) {
            get_footer();
        } else {
            wp_footer();
            echo '</body>';
            echo '</html>';
        }
    }
    
    /**
     * Display page when no token is provided
     */
    private function display_no_token_page() {
        echo '<div class="sllist-update-error">';
        echo '<h1>' . __('Invalid Access', 'storelocator-list') . '</h1>';
        echo '<p>' . __('This page requires a valid access token. Please use the link provided in your email.', 'storelocator-list') . '</p>';
        $store_manager_url = \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url();
        echo '<p><a href="' . $store_manager_url . '" class="button">' . __('Request Store Access', 'storelocator-list') . '</a></p>';
        echo '</div>';
    }
    
    /**
     * Display authentication page with password form
     * 
     * @param string $token Access token
     */
    private function display_authentication_page($token) {
        // Get basic token info without validating password
        $token_info = $this->get_token_info($token);
        
        if (!$token_info) {
            echo '<div class="sllist-update-error">';
            echo '<h1>' . __('Invalid or Expired Token', 'storelocator-list') . '</h1>';
            echo '<p>' . __('The access token is invalid, expired, or has already been used.', 'storelocator-list') . '</p>';
            $store_manager_url = \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url();
            echo '<p><a href="' . $store_manager_url . '" class="button">' . __('Request New Access', 'storelocator-list') . '</a></p>';
            echo '</div>';
            return;
        }
        
        $store = $token_info['store'];
        
        echo '<div class="sllist-authentication-form">';
        $page_title = \StoreLocatorList\Admin\SLList_Settings::get_setting('update_page_title', 'Store Update Access');
        echo '<h1>' . esc_html($page_title) . '</h1>';
        echo '<div class="store-info">';
        echo '<h2>' . sprintf(__('Updating: %s', 'storelocator-list'), esc_html($store->post_title)) . '</h2>';
        
        // Show store basic info
        $store_address = get_post_meta($store->ID, 'wpsl_address', true);
        $store_city = get_post_meta($store->ID, 'wpsl_city', true);
        $store_state = get_post_meta($store->ID, 'wpsl_state', true);
        
        if ($store_address || $store_city || $store_state) {
            echo '<p class="store-location">';
            if ($store_address) echo esc_html($store_address);
            if ($store_city || $store_state) {
                if ($store_address) echo ', ';
                echo esc_html(trim($store_city . ' ' . $store_state));
            }
            echo '</p>';
        }
        
        echo '</div>'; // .store-info
        
        // Show security warnings if there are failed attempts
        if ($token_info['attempts'] > 0) {
            echo '<div class="sllist-security-warning">';
            echo '<p><strong>' . __('Security Notice:', 'storelocator-list') . '</strong> ';
            echo sprintf(
                _n('%d failed authentication attempt detected.', '%d failed authentication attempts detected.', $token_info['attempts'], 'storelocator-list'),
                $token_info['attempts']
            );
            echo '</p>';
            echo '</div>';
        }
        
        // Authentication form
        echo '<form id="sllist-auth-form" method="post">';
        wp_nonce_field('sllist_auth_nonce', 'sllist_auth_nonce');
        
        echo '<input type="hidden" name="token" value="' . esc_attr($token) . '">';
        
        echo '<div class="form-group">';
        echo '<label for="access_password">' . __('Access Password', 'storelocator-list') . '</label>';
        echo '<input type="password" id="access_password" name="access_password" required maxlength="50" autocomplete="current-password">';
        echo '<p class="help-text">' . __('Enter the password sent to your email address.', 'storelocator-list') . '</p>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<button type="submit" class="button button-primary">' . __('Access Store Update', 'storelocator-list') . '</button>';
        echo '</div>';
        
        echo '</form>';
        
        // Show expiration info
        if ($token_info['expires']) {
            $expires_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $token_info['expires']);
            echo '<p class="expiry-info"><small>' . sprintf(__('Access expires: %s', 'storelocator-list'), $expires_date) . '</small></p>';
        }
        
        echo '</div>'; // .sllist-authentication-form
    }
    
    /**
     * Display the store update form after successful authentication
     * 
     * @param array $store_data Validated store data
     */
    private function display_store_update_form($store_data) {
        $store = $store_data['store'];
        $token = $store_data['token'];
        
        echo '<div class="sllist-store-update-form">';
        
        $page_title = \StoreLocatorList\Admin\SLList_Settings::get_setting('update_page_title', 'Update Store Details');
        $instructions = \StoreLocatorList\Admin\SLList_Settings::get_setting('update_page_instructions', 'Please update your store information below. All changes will be reviewed and published after submission.');
        
        echo '<h1>' . esc_html($page_title) . '</h1>';
        echo '<h2>' . esc_html($store->post_title) . '</h2>';
        
        if (!empty($instructions)) {
            echo '<p class="update-instructions">' . esc_html($instructions) . '</p>';
        }
        
        echo '<form id="sllist-update-form" method="post">';
        wp_nonce_field('sllist_update_nonce', 'sllist_update_nonce');
        
        echo '<input type="hidden" name="token" value="' . esc_attr($token) . '">';
        echo '<input type="hidden" name="store_id" value="' . esc_attr($store->ID) . '">';
        
        // Get available fields configuration
        $available_fields = \StoreLocatorList\Admin\SLList_Settings::get_available_fields();
        
        // Store Name
        if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('store_name')) {
            $field_config = $available_fields['store_name'];
            $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
            $required_attr = !empty($field_config['required']) ? 'required' : '';
            
            echo '<div class="form-group">';
            echo '<label for="store_name">' . esc_html($field_config['label']) . $required . '</label>';
            echo '<input type="text" id="store_name" name="store_name" value="' . esc_attr($store->post_title) . '" ' . $required_attr . ' maxlength="200">';
            echo '</div>';
        }
        
        // Store Description
        if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('store_description')) {
            $field_config = $available_fields['store_description'];
            $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
            $required_attr = !empty($field_config['required']) ? 'required' : '';
            
            echo '<div class="form-group">';
            echo '<label for="store_description">' . esc_html($field_config['label']) . $required . '</label>';
            echo '<textarea id="store_description" name="store_description" rows="4" maxlength="1000" ' . $required_attr . '>' . esc_textarea($store->post_content) . '</textarea>';
            echo '</div>';
        }
        
        // Address fields
        $address_fields = array('wpsl_address', 'wpsl_address2', 'wpsl_city', 'wpsl_state', 'wpsl_zip');
        $has_address_fields = false;
        foreach ($address_fields as $field) {
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available($field)) {
                $has_address_fields = true;
                break;
            }
        }
        
        if ($has_address_fields) {
            echo '<fieldset>';
            echo '<legend>' . __('Address Information', 'storelocator-list') . '</legend>';
            
            // Street Address
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_address')) {
                $field_config = $available_fields['wpsl_address'];
                $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                $required_attr = !empty($field_config['required']) ? 'required' : '';
                
                echo '<div class="form-group">';
                echo '<label for="wpsl_address">' . esc_html($field_config['label']) . $required . '</label>';
                echo '<input type="text" id="wpsl_address" name="wpsl_address" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_address', true)) . '" maxlength="200" ' . $required_attr . '>';
                echo '</div>';
            }
            
            // Address Line 2
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_address2')) {
                $field_config = $available_fields['wpsl_address2'];
                $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                $required_attr = !empty($field_config['required']) ? 'required' : '';
                
                echo '<div class="form-group">';
                echo '<label for="wpsl_address2">' . esc_html($field_config['label']) . $required . '</label>';
                echo '<input type="text" id="wpsl_address2" name="wpsl_address2" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_address2', true)) . '" maxlength="200" ' . $required_attr . '>';
                echo '</div>';
            }
            
            // City and State row
            $has_city = \StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_city');
            $has_state = \StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_state');
            
            if ($has_city || $has_state) {
                echo '<div class="form-row">';
                
                if ($has_city) {
                    $field_config = $available_fields['wpsl_city'];
                    $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                    $required_attr = !empty($field_config['required']) ? 'required' : '';
                    
                    echo '<div class="form-group form-group-half">';
                    echo '<label for="wpsl_city">' . esc_html($field_config['label']) . $required . '</label>';
                    echo '<input type="text" id="wpsl_city" name="wpsl_city" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_city', true)) . '" maxlength="100" ' . $required_attr . '>';
                    echo '</div>';
                }
                
                if ($has_state) {
                    $field_config = $available_fields['wpsl_state'];
                    $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                    $required_attr = !empty($field_config['required']) ? 'required' : '';
                    
                    echo '<div class="form-group form-group-half">';
                    echo '<label for="wpsl_state">' . esc_html($field_config['label']) . $required . '</label>';
                    echo '<input type="text" id="wpsl_state" name="wpsl_state" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_state', true)) . '" maxlength="100" ' . $required_attr . '>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
            // ZIP Code
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_zip')) {
                $field_config = $available_fields['wpsl_zip'];
                $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                $required_attr = !empty($field_config['required']) ? 'required' : '';
                
                echo '<div class="form-group">';
                echo '<label for="wpsl_zip">' . esc_html($field_config['label']) . $required . '</label>';
                echo '<input type="text" id="wpsl_zip" name="wpsl_zip" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_zip', true)) . '" maxlength="20" ' . $required_attr . '>';
                echo '</div>';
            }
            
            echo '</fieldset>';
        }
        
        // Contact Information
        $contact_fields = array('wpsl_phone', 'wpsl_email', 'wpsl_url');
        $has_contact_fields = false;
        foreach ($contact_fields as $field) {
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available($field)) {
                $has_contact_fields = true;
                break;
            }
        }
        
        if ($has_contact_fields) {
            echo '<fieldset>';
            echo '<legend>' . __('Contact Information', 'storelocator-list') . '</legend>';
            
            // Phone Number
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_phone')) {
                $field_config = $available_fields['wpsl_phone'];
                $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                $required_attr = !empty($field_config['required']) ? 'required' : '';
                
                echo '<div class="form-group">';
                echo '<label for="wpsl_phone">' . esc_html($field_config['label']) . $required . '</label>';
                echo '<input type="tel" id="wpsl_phone" name="wpsl_phone" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_phone', true)) . '" maxlength="50" ' . $required_attr . '>';
                echo '</div>';
            }
            
            // Email Address
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_email')) {
                $field_config = $available_fields['wpsl_email'];
                $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                $required_attr = !empty($field_config['required']) ? 'required' : '';
                
                echo '<div class="form-group">';
                echo '<label for="wpsl_email">' . esc_html($field_config['label']) . $required . '</label>';
                echo '<input type="email" id="wpsl_email" name="wpsl_email" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_email', true)) . '" maxlength="100" ' . $required_attr . '>';
                echo '<p class="help-text">' . __('This email will be used for future access requests.', 'storelocator-list') . '</p>';
                echo '</div>';
            }
            
            // Website URL
            if (\StoreLocatorList\Admin\SLList_Settings::is_field_available('wpsl_url')) {
                $field_config = $available_fields['wpsl_url'];
                $required = !empty($field_config['required']) ? ' <span class="required">*</span>' : '';
                $required_attr = !empty($field_config['required']) ? 'required' : '';
                
                echo '<div class="form-group">';
                echo '<label for="wpsl_url">' . esc_html($field_config['label']) . $required . '</label>';
                echo '<input type="url" id="wpsl_url" name="wpsl_url" value="' . esc_attr(get_post_meta($store->ID, 'wpsl_url', true)) . '" maxlength="200" ' . $required_attr . '>';
                echo '</div>';
            }
            
            echo '</fieldset>';
        }
        
        // Submit buttons
        echo '<div class="form-actions">';
        echo '<button type="submit" class="button button-primary button-large">' . __('Update Store Details', 'storelocator-list') . '</button>';
        $store_manager_url = \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url();
        echo '<a href="' . $store_manager_url . '" class="button button-secondary">' . __('Cancel', 'storelocator-list') . '</a>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>'; // .sllist-store-update-form
    }
    
    /**
     * Get token information without password validation
     * 
     * @param string $token Access token
     * @return array|false Token info or false if invalid
     */
    private function get_token_info($token) {
        if (empty($token)) {
            return false;
        }
        
        // Find store by token
        $stores = get_posts([
            'post_type' => 'wpsl_stores',
            'meta_key' => SLList_Security::META_ACCESS_TOKEN,
            'meta_value' => sanitize_text_field($token),
            'meta_compare' => '=',
            'posts_per_page' => 1,
            'post_status' => ['publish', 'pending']
        ]);
        
        if (empty($stores)) {
            return false;
        }
        
        $store = $stores[0];
        $store_id = $store->ID;
        
        // Check if token is already used
        $token_used = get_post_meta($store_id, SLList_Security::META_TOKEN_USED, true);
        if ($token_used) {
            return false;
        }
        
        // Check if token is expired
        $expires = get_post_meta($store_id, SLList_Security::META_TOKEN_EXPIRES, true);
        if ($expires && time() > $expires) {
            return false;
        }
        
        // Get additional info
        $attempts = get_post_meta($store_id, SLList_Security::META_ACCESS_ATTEMPTS, true) ?: 0;
        
        return [
            'store' => $store,
            'store_id' => $store_id,
            'token' => $token,
            'expires' => $expires,
            'attempts' => $attempts,
            'used' => $token_used
        ];
    }
    
    /**
     * Enqueue scripts and styles for store update page
     */
    public function enqueue_scripts() {
        // Check if this is our store update page
        $is_store_update = false;
        
        if (get_query_var('sllist_page') === 'store_update' || 
            (isset($_GET['sllist_page']) && $_GET['sllist_page'] === 'store_update') ||
            preg_match('#/update-store/?(\?.*)?$#', $_SERVER['REQUEST_URI'] ?? '')) {
            $is_store_update = true;
        }
        
        // Only enqueue on our update page
        if (!$is_store_update) {
            return;
        }
        
        // Get plugin instance to access URLs
        $plugin = \StoreLocatorList\SLList_Plugin::get_instance();
        
        // Enqueue jQuery if not already loaded
        if (!wp_script_is('jquery', 'enqueued')) {
            wp_enqueue_script('jquery');
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'sllist-store-update',
            $plugin->get_plugin_url() . 'assets/css/store-update.css',
            array(),
            \StoreLocatorList\SLList_Plugin::VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'sllist-store-update',
            $plugin->get_plugin_url() . 'assets/js/store-update.js',
            array('jquery'),
            \StoreLocatorList\SLList_Plugin::VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('sllist-store-update', 'sllist_update', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'auth_nonce' => wp_create_nonce('sllist_auth_nonce'),
            'update_nonce' => wp_create_nonce('sllist_update_nonce'),
            'strings' => array(
                'authenticating' => __('Authenticating...', 'storelocator-list'),
                'updating' => __('Updating store details...', 'storelocator-list'),
                'error' => __('An error occurred. Please try again.', 'storelocator-list'),
                'invalid_password' => __('Invalid password. Please check your email and try again.', 'storelocator-list'),
                'success' => __('Store details updated successfully!', 'storelocator-list'),
                'too_many_attempts' => __('Too many failed attempts. Please request new access.', 'storelocator-list')
            )
        ));
    }
    
    /**
     * AJAX handler for authenticating store access
     */
    public function ajax_authenticate_store_access() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['sllist_auth_nonce'] ?? '', 'sllist_auth_nonce')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Security check failed. Please refresh the page and try again.', 'storelocator-list')
            )));
        }
        
        $token = sanitize_text_field($_POST['token'] ?? '');
        $password = sanitize_text_field($_POST['access_password'] ?? '');
        
        if (empty($token) || empty($password)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Token and password are required.', 'storelocator-list')
            )));
        }
        
        // Validate credentials using security class
        $store_data = SLList_Security::validate_access_credentials($token, $password);
        
        if (!$store_data) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Invalid password or token. Please check your credentials and try again.', 'storelocator-list')
            )));
        }
        
        // Success - return HTML for the update form
        ob_start();
        $this->display_store_update_form($store_data);
        $form_html = ob_get_clean();
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'html' => $form_html,
                'store_name' => $store_data['store']->post_title
            )
        )));
    }
    
    /**
     * AJAX handler for updating store details
     */
    public function ajax_update_store_details() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['sllist_update_nonce'] ?? '', 'sllist_update_nonce')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Security check failed. Please refresh the page and try again.', 'storelocator-list')
            )));
        }
        
        $token = sanitize_text_field($_POST['token'] ?? '');
        $store_id = intval($_POST['store_id'] ?? 0);
        
        if (empty($token) || $store_id <= 0) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Invalid request parameters.', 'storelocator-list')
            )));
        }
        
        // Validate token is still valid and get store data
        $token_info = $this->get_token_info($token);
        if (!$token_info || $token_info['store_id'] !== $store_id) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Invalid or expired access token.', 'storelocator-list')
            )));
        }
        
        // Sanitize and validate form data
        $store_data = $this->sanitize_store_update_data($_POST);
        $validation_errors = $this->validate_store_update_data($store_data);
        
        if (!empty($validation_errors)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => implode(' ', $validation_errors)
            )));
        }
        
        // Update the store
        $update_result = $this->update_store_data($store_id, $store_data);
        
        if (!$update_result) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => __('Failed to update store details. Please try again.', 'storelocator-list')
            )));
        }
        
        // Mark token as used
        SLList_Security::mark_token_used($store_id);
        
        // Send confirmation email
        $this->send_update_confirmation_email($token_info['store'], $store_data);

        // Create success page URL
        $success_url = add_query_arg(array(
            'success' => '1',
            'store_name' => urlencode($store_data['store_name'])
        ), home_url('/update-store/'));

        wp_die(json_encode(array(
            'success' => true,
            'data' => __('Store details updated successfully! Your changes have been saved.', 'storelocator-list'),
            'redirect_url' => $success_url
        )));
    }
    
    /**
     * Sanitize store update data
     * 
     * @param array $post_data Raw POST data
     * @return array Sanitized data
     */
    private function sanitize_store_update_data($post_data) {
        return array(
            'store_name' => sanitize_text_field($post_data['store_name'] ?? ''),
            'store_description' => sanitize_textarea_field($post_data['store_description'] ?? ''),
            'wpsl_address' => sanitize_text_field($post_data['wpsl_address'] ?? ''),
            'wpsl_address2' => sanitize_text_field($post_data['wpsl_address2'] ?? ''),
            'wpsl_city' => sanitize_text_field($post_data['wpsl_city'] ?? ''),
            'wpsl_state' => sanitize_text_field($post_data['wpsl_state'] ?? ''),
            'wpsl_zip' => sanitize_text_field($post_data['wpsl_zip'] ?? ''),
            'wpsl_phone' => sanitize_text_field($post_data['wpsl_phone'] ?? ''),
            'wpsl_email' => sanitize_email($post_data['wpsl_email'] ?? ''),
            'wpsl_url' => esc_url_raw($post_data['wpsl_url'] ?? '')
        );
    }
    
    /**
     * Validate store update data
     * 
     * @param array $store_data Sanitized store data
     * @return array Validation errors
     */
    private function validate_store_update_data($store_data) {
        $errors = array();
        
        // Store name is required
        if (empty($store_data['store_name'])) {
            $errors[] = __('Store name is required.', 'storelocator-list');
        }
        
        // Validate email if provided
        if (!empty($store_data['wpsl_email']) && !is_email($store_data['wpsl_email'])) {
            $errors[] = __('Please enter a valid email address.', 'storelocator-list');
        }
        
        // Validate URL if provided
        if (!empty($store_data['wpsl_url']) && !filter_var($store_data['wpsl_url'], FILTER_VALIDATE_URL)) {
            $errors[] = __('Please enter a valid website URL.', 'storelocator-list');
        }
        
        return $errors;
    }
    
    /**
     * Update store data in the database
     * 
     * @param int $store_id Store post ID
     * @param array $store_data Validated store data
     * @return bool Success status
     */
    private function update_store_data($store_id, $store_data) {
        // Update post data
        $post_update = wp_update_post(array(
            'ID' => $store_id,
            'post_title' => $store_data['store_name'],
            'post_content' => $store_data['store_description'],
        ), true);
        
        if (is_wp_error($post_update)) {
            return false;
        }
        
        // Update post meta
        $meta_fields = array(
            'wpsl_address' => $store_data['wpsl_address'],
            'wpsl_address2' => $store_data['wpsl_address2'],
            'wpsl_city' => $store_data['wpsl_city'],
            'wpsl_state' => $store_data['wpsl_state'],
            'wpsl_zip' => $store_data['wpsl_zip'],
            'wpsl_phone' => $store_data['wpsl_phone'],
            'wpsl_email' => $store_data['wpsl_email'],
            'wpsl_url' => $store_data['wpsl_url']
        );
        
        foreach ($meta_fields as $meta_key => $meta_value) {
            update_post_meta($store_id, $meta_key, $meta_value);
        }
        
        // Log the update
        $this->log_store_update($store_id, $store_data);
        
        return true;
    }
    
    /**
     * Log store update for audit trail
     * 
     * @param int $store_id Store post ID
     * @param array $store_data Updated store data
     */
    private function log_store_update($store_id, $store_data) {
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'store_id' => $store_id,
            'updated_fields' => array_keys($store_data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        // Store as post meta for audit purposes
        add_post_meta($store_id, 'sllist_update_log', $log_data);
    }
    
    /**
     * Send update confirmation email to store owner
     * 
     * @param \WP_Post $store Store post object
     * @param array $store_data Updated store data
     */
    private function send_update_confirmation_email($store, $store_data) {
        $store_email = $store_data['wpsl_email'];
        
        // Don't send if no email
        if (empty($store_email)) {
            return;
        }
        
        $subject_template = \StoreLocatorList\Admin\SLList_Settings::get_setting('confirmation_email_subject', 'Store Details Updated Successfully - {store_name}');
        $subject = str_replace('{store_name}', $store_data['store_name'], $subject_template);
        $subject = str_replace('{site_name}', get_bloginfo('name'), $subject);
        
        $message = $this->get_confirmation_email_template($store, $store_data);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($store_email, $subject, $message, $headers);
    }
    
    /**
     * Get confirmation email template
     * 
     * @param \WP_Post $store Store post object
     * @param array $store_data Updated store data
     * @return string Email HTML content
     */
    private function get_confirmation_email_template($store, $store_data) {
        $store_name = esc_html($store_data['store_name']);
        $site_name = get_bloginfo('name');
        $update_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
        
        // Get customizable content from settings
        $email_header = \StoreLocatorList\Admin\SLList_Settings::get_setting('confirmation_email_header', 'Store Details Updated Successfully');
        $email_intro = \StoreLocatorList\Admin\SLList_Settings::get_setting('confirmation_email_intro', 'Your store details have been successfully updated on {site_name} at {update_date}.');
        $security_info = \StoreLocatorList\Admin\SLList_Settings::get_setting('confirmation_email_security_info', 'Your access token has been automatically deactivated for security. If you need to make additional changes, please request new access from the store manager page.');
        
        // Get email styling from settings
        $header_color = \StoreLocatorList\Admin\SLList_Settings::get_setting('email_header_color', '#f8f9fa');
        $button_color = \StoreLocatorList\Admin\SLList_Settings::get_setting('email_button_color', '#0073aa');
        
        // Replace placeholders
        $email_intro = str_replace('{site_name}', $site_name, $email_intro);
        $email_intro = str_replace('{store_name}', $store_name, $email_intro);
        $email_intro = str_replace('{update_date}', $update_date, $email_intro);
        
        $store_manager_url = \StoreLocatorList\PublicPages\SLList_Store_Search::get_store_manager_url();
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . esc_html($email_header) . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: ' . esc_attr($header_color) . '; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { padding: 20px 0; }
                .button { display: inline-block; background: ' . esc_attr($button_color) . '; color: white !important; padding: 12px 20px; text-decoration: none; border-radius: 3px; margin: 10px 0; }
                .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 3px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . esc_html($email_header) . '</h1>
                    <p>Confirmation for: <strong>' . $store_name . '</strong></p>
                </div>
                
                <div class="content">
                    <div class="success">
                        <h3>✓ Update Confirmed</h3>
                        <p>' . esc_html($email_intro) . '</p>
                    </div>
                    
                    <h3>Updated Information:</h3>
                    <ul>
                        <li><strong>Store Name:</strong> ' . esc_html($store_data['store_name']) . '</li>';
        
        if (!empty($store_data['wpsl_address'])) {
            $message .= '<li><strong>Address:</strong> ' . esc_html($store_data['wpsl_address']) . '</li>';
        }
        
        if (!empty($store_data['wpsl_city']) || !empty($store_data['wpsl_state'])) {
            $message .= '<li><strong>City/State:</strong> ' . esc_html(trim($store_data['wpsl_city'] . ' ' . $store_data['wpsl_state'])) . '</li>';
        }
        
        if (!empty($store_data['wpsl_phone'])) {
            $message .= '<li><strong>Phone:</strong> ' . esc_html($store_data['wpsl_phone']) . '</li>';
        }
        
        if (!empty($store_data['wpsl_email'])) {
            $message .= '<li><strong>Email:</strong> ' . esc_html($store_data['wpsl_email']) . '</li>';
        }
        
        $message .= '
                    </ul>
                    
                    <h3>Security Information:</h3>
                    <p>' . esc_html($security_info) . '</p>
                    
                    <a href="' . esc_url($store_manager_url) . '" class="button">Request New Access</a>
                    
                    <p><small>If you did not make these changes, please contact us immediately at ' . get_option('admin_email') . '</small></p>
                </div>
            </div>
        </body>
        </html>';
        
        return $message;
    }
}
