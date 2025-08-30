<?php
/**
 * Store Locator List Settings functionality
 * 
 * @package StoreLocator-List
 * @since 0.2.2
 */

namespace StoreLocatorList\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Settings {
    
    /**
     * Settings option name
     */
    const OPTION_NAME = 'sllist_settings';
    
    /**
     * Default settings
     */
    private static $defaults = array(
        // Store Manager Page Settings
        'store_manager_title' => 'Store Manager Portal',
        'store_manager_subtitle' => 'Find and update your store information',
        'store_manager_instructions' => 'Search for your store below and click "Request Access" to receive update credentials via email.',
        'store_manager_search_placeholder' => 'Enter store name, city, or address...',
        'store_manager_permalink' => 'store-manager',
        
        // Update Page Settings
        'update_page_title' => 'Update Store Details',
        'update_page_instructions' => 'Please update your store information below. All changes will be reviewed and published after submission.',
        'update_success_title' => 'Update Successful!',
        'update_success_message' => 'Your store listing has been updated successfully! Your changes have been saved and are now live on the website. You will also receive a confirmation email shortly.',
        'update_permalink' => 'update-store',
        
        // Access Request Email Settings
        'access_email_subject' => 'Store Update Access - {store_name}',
        'access_email_header' => 'Store Update Access Request',
        'access_email_greeting' => 'Hello,',
        'access_email_intro' => 'You have requested access to update your store details on {site_name}. Please use the following credentials to access your store update page:',
        'access_email_button_text' => 'Update Your Store Details',
        'access_email_instructions' => 'If the button above doesn\'t work, copy and paste this URL into your browser:',
        'access_email_updatable_fields' => 'Store name and description|Address and contact information|Phone number and email|Business hours',
        'access_email_security_notice' => 'If you did not request this access, please ignore this email. The access will expire automatically.',
        
        // Confirmation Email Settings
        'confirmation_email_subject' => 'Store Details Updated Successfully - {store_name}',
        'confirmation_email_header' => 'Store Details Updated Successfully',
        'confirmation_email_intro' => 'Your store details have been successfully updated on {site_name} at {update_date}.',
        'confirmation_email_security_info' => 'Your access token has been automatically deactivated for security. If you need to make additional changes, please request new access from the store manager page.',
        
        // Available Fields Settings
        'available_fields' => array(
            'store_name' => array(
                'enabled' => true,
                'required' => true,
                'label' => 'Store Name'
            ),
            'store_description' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'Store Description'
            ),
            'wpsl_address' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'Street Address'
            ),
            'wpsl_address2' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'Address Line 2'
            ),
            'wpsl_city' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'City'
            ),
            'wpsl_state' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'State/Province'
            ),
            'wpsl_zip' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'ZIP/Postal Code'
            ),
            'wpsl_phone' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'Phone Number'
            ),
            'wpsl_email' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'Email Address'
            ),
            'wpsl_url' => array(
                'enabled' => true,
                'required' => false,
                'label' => 'Website URL'
            )
        ),
        
        // Email Styling Settings
        'email_header_color' => '#f8f9fa',
        'email_button_color' => '#007cba',
        'email_warning_color' => '#fff3cd'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 25); // After Import/Export
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Check if Store Locator plugin is active
        if (!post_type_exists('wpsl_stores')) {
            return;
        }
        
        add_submenu_page(
            'edit.php?post_type=wpsl_stores',
            __('SL List Settings', 'storelocator-list'),
            __('SL List Settings', 'storelocator-list'),
            'manage_options',
            'sllist-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'sllist_settings',
            self::OPTION_NAME,
            array(
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => self::$defaults
            )
        );
        
        // Store Manager Page Settings Section
        add_settings_section(
            'sllist_store_manager_section',
            __('Store Manager Page Settings', 'storelocator-list'),
            array($this, 'store_manager_section_callback'),
            'sllist_settings'
        );
        
        // Update Page Settings Section
        add_settings_section(
            'sllist_update_page_section',
            __('Store Update Page Settings', 'storelocator-list'),
            array($this, 'update_page_section_callback'),
            'sllist_settings'
        );
        
        // Email Settings Section
        add_settings_section(
            'sllist_email_section',
            __('Email Template Settings', 'storelocator-list'),
            array($this, 'email_section_callback'),
            'sllist_settings'
        );
        
        // Available Fields Section
        add_settings_section(
            'sllist_fields_section',
            __('Available Update Fields', 'storelocator-list'),
            array($this, 'fields_section_callback'),
            'sllist_settings'
        );
        
        $this->register_store_manager_fields();
        $this->register_update_page_fields();
        $this->register_email_fields();
        $this->register_available_fields();
    }
    
    /**
     * Register store manager page fields
     */
    private function register_store_manager_fields() {
        $fields = array(
            'store_manager_title' => __('Page Title', 'storelocator-list'),
            'store_manager_subtitle' => __('Page Subtitle', 'storelocator-list'),
            'store_manager_instructions' => __('Instructions Text', 'storelocator-list'),
            'store_manager_search_placeholder' => __('Search Placeholder', 'storelocator-list'),
            'store_manager_permalink' => __('URL Slug', 'storelocator-list')
        );
        
        foreach ($fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'text_field_callback'),
                'sllist_settings',
                'sllist_store_manager_section',
                array(
                    'field' => $field,
                    'class' => $field === 'store_manager_instructions' ? 'large-text' : 'regular-text'
                )
            );
        }
    }
    
    /**
     * Register update page fields
     */
    private function register_update_page_fields() {
        $fields = array(
            'update_page_title' => __('Update Page Title', 'storelocator-list'),
            'update_page_instructions' => __('Update Instructions', 'storelocator-list'),
            'update_success_title' => __('Success Page Title', 'storelocator-list'),
            'update_success_message' => __('Success Message', 'storelocator-list'),
            'update_permalink' => __('Update Page URL Slug', 'storelocator-list')
        );
        
        foreach ($fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'text_field_callback'),
                'sllist_settings',
                'sllist_update_page_section',
                array(
                    'field' => $field,
                    'class' => in_array($field, array('update_page_instructions', 'update_success_message')) ? 'large-text' : 'regular-text'
                )
            );
        }
    }
    
    /**
     * Register email template fields
     */
    private function register_email_fields() {
        // Access Request Email Fields
        $access_fields = array(
            'access_email_subject' => __('Access Email Subject', 'storelocator-list'),
            'access_email_header' => __('Email Header Text', 'storelocator-list'),
            'access_email_greeting' => __('Email Greeting', 'storelocator-list'),
            'access_email_intro' => __('Introduction Text', 'storelocator-list'),
            'access_email_button_text' => __('Button Text', 'storelocator-list'),
            'access_email_instructions' => __('URL Instructions', 'storelocator-list'),
            'access_email_updatable_fields' => __('Updatable Fields List', 'storelocator-list'),
            'access_email_security_notice' => __('Security Notice', 'storelocator-list')
        );
        
        foreach ($access_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'email_field_callback'),
                'sllist_settings',
                'sllist_email_section',
                array(
                    'field' => $field,
                    'type' => $field === 'access_email_updatable_fields' ? 'textarea' : 'text'
                )
            );
        }
        
        // Confirmation Email Fields
        $confirmation_fields = array(
            'confirmation_email_subject' => __('Confirmation Email Subject', 'storelocator-list'),
            'confirmation_email_header' => __('Confirmation Header Text', 'storelocator-list'),
            'confirmation_email_intro' => __('Confirmation Introduction', 'storelocator-list'),
            'confirmation_email_security_info' => __('Security Information Text', 'storelocator-list')
        );
        
        foreach ($confirmation_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'email_field_callback'),
                'sllist_settings',
                'sllist_email_section',
                array(
                    'field' => $field,
                    'type' => 'text'
                )
            );
        }
        
        // Email Styling
        $styling_fields = array(
            'email_header_color' => __('Header Background Color', 'storelocator-list'),
            'email_button_color' => __('Button Color', 'storelocator-list'),
            'email_warning_color' => __('Warning Background Color', 'storelocator-list')
        );
        
        foreach ($styling_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'color_field_callback'),
                'sllist_settings',
                'sllist_email_section',
                array('field' => $field)
            );
        }
    }
    
    /**
     * Register available fields settings
     */
    private function register_available_fields() {
        add_settings_field(
            'available_fields',
            __('Select Available Fields', 'storelocator-list'),
            array($this, 'available_fields_callback'),
            'sllist_settings',
            'sllist_fields_section'
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'wpsl_stores_page_sllist-settings') {
            return;
        }
        
        // Enqueue color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        $plugin = \StoreLocatorList\SLList_Plugin::get_instance();
        
        wp_enqueue_style(
            'sllist-settings',
            $plugin->get_plugin_url() . 'assets/css/sllist-settings.css',
            array(),
            \StoreLocatorList\SLList_Plugin::VERSION
        );
        
        wp_enqueue_script(
            'sllist-settings',
            $plugin->get_plugin_url() . 'assets/js/sllist-settings.js',
            array('jquery', 'wp-color-picker'),
            \StoreLocatorList\SLList_Plugin::VERSION,
            true
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle flush rewrite rules action
        if (isset($_GET['action']) && $_GET['action'] === 'flush_rewrite_rules' && wp_verify_nonce($_GET['_wpnonce'], 'flush_rewrite_rules')) {
            flush_rewrite_rules(true);
            add_settings_error(
                'sllist_settings',
                'rewrite_rules_flushed',
                __('Rewrite rules have been flushed successfully! Your new permalink settings should now work.', 'storelocator-list'),
                'updated'
            );
        }
        
        // Handle form submission
        if (isset($_POST['submit'])) {
            // WordPress will handle the settings update via the Settings API
            add_settings_error(
                'sllist_settings',
                'settings_updated',
                __('Settings saved successfully!', 'storelocator-list'),
                'updated'
            );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('SL List Settings', 'storelocator-list'); ?></h1>
            
            <?php settings_errors('sllist_settings'); ?>
            
            <div class="sllist-settings-container">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('sllist_settings');
                    do_settings_sections('sllist_settings');
                    submit_button();
                    ?>
                </form>
                
                <div class="sllist-settings-help">
                    <h3><?php echo esc_html__('Troubleshooting', 'storelocator-list'); ?></h3>
                    <div class="sllist-troubleshooting">
                        <p><?php echo esc_html__('If your custom permalink URLs are not working (showing 404 errors), try the following:', 'storelocator-list'); ?></p>
                        <ol>
                            <li><?php echo esc_html__('Go to Settings > Permalinks in your WordPress admin and click "Save Changes"', 'storelocator-list'); ?></li>
                            <li><?php echo esc_html__('Or click the button below to force flush rewrite rules:', 'storelocator-list'); ?></li>
                        </ol>
                        <p>
                            <a href="<?php echo wp_nonce_url(add_query_arg('action', 'flush_rewrite_rules'), 'flush_rewrite_rules'); ?>" 
                               class="button button-secondary" 
                               onclick="return confirm('<?php echo esc_js(__('This will flush WordPress rewrite rules. Continue?', 'storelocator-list')); ?>')">
                                <?php echo esc_html__('Flush Rewrite Rules', 'storelocator-list'); ?>
                            </a>
                        </p>
                    </div>
                    
                    <h3><?php echo esc_html__('Available Placeholders', 'storelocator-list'); ?></h3>
                    <div class="sllist-placeholders">
                        <div class="placeholder-group">
                            <h4><?php echo esc_html__('General Placeholders', 'storelocator-list'); ?></h4>
                            <ul>
                                <li><code>{site_name}</code> - <?php echo esc_html__('Website name', 'storelocator-list'); ?></li>
                                <li><code>{store_name}</code> - <?php echo esc_html__('Store name', 'storelocator-list'); ?></li>
                                <li><code>{update_date}</code> - <?php echo esc_html__('Date and time of update', 'storelocator-list'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="placeholder-group">
                            <h4><?php echo esc_html__('URL Settings', 'storelocator-list'); ?></h4>
                            <p><?php echo esc_html__('Changing URL slugs will require you to flush permalinks. Go to Settings → Permalinks and click "Save Changes" after updating these settings.', 'storelocator-list'); ?></p>
                        </div>
                        
                        <div class="placeholder-group">
                            <h4><?php echo esc_html__('Field Labels', 'storelocator-list'); ?></h4>
                            <p><?php echo esc_html__('You can customize field labels and control which fields are available for store owners to update. Required fields cannot be disabled.', 'storelocator-list'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Section callbacks
     */
    public function store_manager_section_callback() {
        echo '<p>' . esc_html__('Customize the text and URL settings for the store manager search page.', 'storelocator-list') . '</p>';
    }
    
    public function update_page_section_callback() {
        echo '<p>' . esc_html__('Customize the text and URL settings for the store update pages.', 'storelocator-list') . '</p>';
    }
    
    public function email_section_callback() {
        echo '<p>' . esc_html__('Customize the email templates sent to store owners. Use placeholders like {store_name} and {site_name} for dynamic content.', 'storelocator-list') . '</p>';
    }
    
    public function fields_section_callback() {
        echo '<p>' . esc_html__('Choose which fields store owners can update and customize their labels.', 'storelocator-list') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function text_field_callback($args) {
        $settings = self::get_settings();
        $field = $args['field'];
        $class = isset($args['class']) ? $args['class'] : 'regular-text';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        if ($class === 'large-text') {
            echo '<textarea name="' . esc_attr(self::OPTION_NAME . '[' . $field . ']') . '" class="' . esc_attr($class) . '" rows="3">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input type="text" name="' . esc_attr(self::OPTION_NAME . '[' . $field . ']') . '" value="' . esc_attr($value) . '" class="' . esc_attr($class) . '" />';
        }
        
        // Add description for permalink fields
        if (strpos($field, 'permalink') !== false) {
            echo '<p class="description">' . esc_html__('URL slug (letters, numbers, and hyphens only)', 'storelocator-list') . '</p>';
        }
    }
    
    public function email_field_callback($args) {
        $settings = self::get_settings();
        $field = $args['field'];
        $type = isset($args['type']) ? $args['type'] : 'text';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        if ($type === 'textarea') {
            echo '<textarea name="' . esc_attr(self::OPTION_NAME . '[' . $field . ']') . '" class="large-text" rows="4">' . esc_textarea($value) . '</textarea>';
            if ($field === 'access_email_updatable_fields') {
                echo '<p class="description">' . esc_html__('Separate each item with a pipe (|) character', 'storelocator-list') . '</p>';
            }
        } else {
            echo '<input type="text" name="' . esc_attr(self::OPTION_NAME . '[' . $field . ']') . '" value="' . esc_attr($value) . '" class="large-text" />';
        }
    }
    
    public function color_field_callback($args) {
        $settings = self::get_settings();
        $field = $args['field'];
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        echo '<input type="text" name="' . esc_attr(self::OPTION_NAME . '[' . $field . ']') . '" value="' . esc_attr($value) . '" class="sllist-color-picker" />';
    }
    
    public function available_fields_callback() {
        $settings = self::get_settings();
        $available_fields = isset($settings['available_fields']) ? $settings['available_fields'] : self::$defaults['available_fields'];
        
        echo '<div class="sllist-fields-grid">';
        
        foreach ($available_fields as $field_key => $field_data) {
            $enabled = isset($field_data['enabled']) ? $field_data['enabled'] : true;
            $required = isset($field_data['required']) ? $field_data['required'] : false;
            $label = isset($field_data['label']) ? $field_data['label'] : $field_key;
            
            echo '<div class="sllist-field-row">';
            echo '<div class="sllist-field-checkbox">';
            
            if ($required) {
                echo '<input type="checkbox" checked="checked" disabled="disabled" />';
                echo '<input type="hidden" name="' . esc_attr(self::OPTION_NAME . '[available_fields][' . $field_key . '][enabled]') . '" value="1" />';
                echo '<span class="description">' . esc_html__('Required', 'storelocator-list') . '</span>';
            } else {
                echo '<input type="checkbox" name="' . esc_attr(self::OPTION_NAME . '[available_fields][' . $field_key . '][enabled]') . '" value="1" ' . checked($enabled, true, false) . ' />';
            }
            
            echo '</div>';
            echo '<div class="sllist-field-label">';
            echo '<input type="text" name="' . esc_attr(self::OPTION_NAME . '[available_fields][' . $field_key . '][label]') . '" value="' . esc_attr($label) . '" class="regular-text" />';
            echo '<input type="hidden" name="' . esc_attr(self::OPTION_NAME . '[available_fields][' . $field_key . '][required]') . '" value="' . ($required ? '1' : '0') . '" />';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '<p class="description">' . esc_html__('Check the fields you want store owners to be able to update, and customize their labels.', 'storelocator-list') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Get current settings to check for permalink changes
        $current_settings = get_option('sllist_settings', array());
        $permalink_changed = false;
        
        // Sanitize text fields
        $text_fields = array(
            'store_manager_title', 'store_manager_subtitle', 'store_manager_instructions',
            'store_manager_search_placeholder', 'update_page_title', 'update_page_instructions',
            'update_success_title', 'update_success_message', 'access_email_subject',
            'access_email_header', 'access_email_greeting', 'access_email_intro',
            'access_email_button_text', 'access_email_instructions', 'access_email_updatable_fields',
            'access_email_security_notice', 'confirmation_email_subject', 'confirmation_email_header',
            'confirmation_email_intro', 'confirmation_email_security_info'
        );
        
        foreach ($text_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_textarea_field($input[$field]);
            }
        }
        
        // Sanitize permalink fields and check for changes
        $permalink_fields = array('store_manager_permalink', 'update_permalink');
        foreach ($permalink_fields as $field) {
            if (isset($input[$field])) {
                $new_value = sanitize_title($input[$field]);
                $sanitized[$field] = $new_value;
                
                // Check if permalink changed
                if (isset($current_settings[$field]) && $current_settings[$field] !== $new_value) {
                    $permalink_changed = true;
                }
            }
        }
        
        // Sanitize color fields
        $color_fields = array('email_header_color', 'email_button_color', 'email_warning_color');
        foreach ($color_fields as $field) {
            if (isset($input[$field])) {
                $sanitized[$field] = sanitize_hex_color($input[$field]);
            }
        }
        
        // Sanitize available fields
        if (isset($input['available_fields']) && is_array($input['available_fields'])) {
            $sanitized['available_fields'] = array();
            foreach ($input['available_fields'] as $field_key => $field_data) {
                $sanitized['available_fields'][$field_key] = array(
                    'enabled' => !empty($field_data['enabled']),
                    'required' => !empty($field_data['required']),
                    'label' => sanitize_text_field($field_data['label'])
                );
            }
        }
        
        // If permalink changed, schedule rewrite rules flush
        if ($permalink_changed) {
            update_option('sllist_flush_rewrite_rules', true);
        }
        
        return $sanitized;
    }
    
    /**
     * Get settings with defaults
     */
    public static function get_settings() {
        $settings = get_option(self::OPTION_NAME, array());
        return wp_parse_args($settings, self::$defaults);
    }
    
    /**
     * Get a specific setting
     */
    public static function get_setting($key, $default = null) {
        $settings = self::get_settings();
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        return $default !== null ? $default : (isset(self::$defaults[$key]) ? self::$defaults[$key] : '');
    }
    
    /**
     * Get available fields configuration
     */
    public static function get_available_fields() {
        return self::get_setting('available_fields', self::$defaults['available_fields']);
    }
    
    /**
     * Check if a field is available for updating
     */
    public static function is_field_available($field_key) {
        $available_fields = self::get_available_fields();
        return isset($available_fields[$field_key]) && !empty($available_fields[$field_key]['enabled']);
    }
    
    /**
     * Get field label
     */
    public static function get_field_label($field_key) {
        $available_fields = self::get_available_fields();
        if (isset($available_fields[$field_key]['label'])) {
            return $available_fields[$field_key]['label'];
        }
        
        // Fallback to default label
        if (isset(self::$defaults['available_fields'][$field_key]['label'])) {
            return self::$defaults['available_fields'][$field_key]['label'];
        }
        
        return $field_key;
    }
}
