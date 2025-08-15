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
        // Add query vars early - before init
        add_filter('query_vars', array($this, 'add_query_vars'), 10, 1);
        
        add_action('init', array($this, 'init_store_search'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sllist_search_stores', array($this, 'ajax_search_stores'));
        add_action('wp_ajax_nopriv_sllist_search_stores', array($this, 'ajax_search_stores'));
        
        // Handle page requests early
        add_action('template_redirect', array($this, 'handle_store_manager_page'), 5);
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
        // Add rewrite rules for our custom pages
        add_rewrite_rule(
            '^store-manager/?$',
            'index.php?sllist_page=store_manager',
            'top'
        );
    }
    
    /**
     * Add query variables
     * 
     * @param array $vars Query variables
     * @return array Modified query variables
     */
    public function add_query_vars($vars) {
        if (!is_array($vars)) {
            $vars = array();
        }
        $vars[] = 'sllist_page';
        return $vars;
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
     * Display the store manager search page
     */
    public function display_store_manager_page() {
        // Check for rate limiting
        if (SLList_Security::is_rate_limited('store_search')) {
            $this->display_rate_limited_page();
            return;
        }
        
        // Check if we have a proper theme with header/footer
        $theme_has_header = locate_template('header.php');
        $theme_has_footer = locate_template('footer.php');
        
        if ($theme_has_header) {
            get_header();
        } else {
            // Minimal HTML header for themes without header.php
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
        
        if ($theme_has_footer) {
            get_footer();
        } else {
            // Minimal HTML footer for themes without footer.php
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
            (isset($_GET['sllist_page']) && $_GET['sllist_page'] === 'store_manager')) {
            $is_store_manager = true;
        }
        
        // Only enqueue on store manager page
        if ($is_store_manager) {
            // Get plugin instance to access URLs
            $plugin = \StoreLocatorList\SLList_Plugin::get_instance();
            
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
