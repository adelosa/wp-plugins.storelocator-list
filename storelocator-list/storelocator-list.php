<?php
/**
 * @package StoreLocator-List
 * @version 0.2.1
 */
/*
Plugin Name: WP Store Locator List
Plugin URI: https://github.com/adelosa/wp-plugins.storelocator-list
Description: Provides ability to add list of stores via shortcode with self-service store owner updates
Author: Anthony Delosa
Version: 0.2.1
Text Domain: storelocator-list
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Network: false
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SLLIST_VERSION', '0.2.1');
define('SLLIST_PLUGIN_FILE', __FILE__);
define('SLLIST_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SLLIST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Register query variables early - this must happen before WordPress processes rewrite rules
 */
function sllist_add_query_vars($vars) {
    if (!is_array($vars)) {
        $vars = array();
    }
    
    // Add our custom query variable
    if (!in_array('sllist_page', $vars)) {
        $vars[] = 'sllist_page';
    }
    
    return $vars;
}
add_filter('query_vars', 'sllist_add_query_vars', 1); // Very high priority

/**
 * Add rewrite rules early
 */
function sllist_add_rewrite_rules() {
    // Add rewrite rule for store manager page
    add_rewrite_rule(
        '^store-manager/?$',
        'index.php?sllist_page=store_manager',
        'top'
    );
    
    // Add rewrite rule for store update page
    add_rewrite_rule(
        '^update-store/?$',
        'index.php?sllist_page=store_update',
        'top'
    );
}
add_action('init', 'sllist_add_rewrite_rules', 1); // Very early in init

/**
 * Load the main plugin class
 */
require_once SLLIST_PLUGIN_PATH . 'includes/class-sllist-plugin.php';

/**
 * Initialize the plugin
 */
function sllist_init_plugin() {
    return \StoreLocatorList\SLList_Plugin::get_instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'sllist_init_plugin');

/**
 * Ensure shortcode is registered (fallback)
 */
function sllist_register_shortcode_fallback() {
    if (!shortcode_exists('sllist')) {
        add_shortcode('sllist', 'sllist_shortcode_fallback');
    }
}
add_action('init', 'sllist_register_shortcode_fallback', 20);

/**
 * Fallback shortcode function
 */
function sllist_shortcode_fallback($atts = [], $content = null, $tag = "") {
    // Ensure the new system is loaded
    if (!class_exists('StoreLocatorList\Core\SLList_Shortcodes')) {
        sllist_init_plugin();
    }
    
    if (class_exists('StoreLocatorList\Core\SLList_Shortcodes')) {
        $shortcodes = new \StoreLocatorList\Core\SLList_Shortcodes();
        return $shortcodes->sllist_shortcode($atts, $content, $tag);
    }
    
    return '<div class="sllist-error">Store locator plugin not properly initialized.</div>';
}

/**
 * Legacy function support for backward compatibility
 * These functions are deprecated and will be removed in a future version
 */

/**
 * @deprecated 0.2.0 Use StoreLocatorList\Core\SLList_Shortcodes::sllist_shortcode() instead
 */
function sllist_shortcode($atts = [], $content = null, $tag = "") {
    return sllist_shortcode_fallback($atts, $content, $tag);
}

/**
 * @deprecated 0.2.0 Use StoreLocatorList\Core\SLList_Query_Helper::add_query_meta() instead
 */
function sllist_add_query_meta($wp_query = "") {
    _deprecated_function(__FUNCTION__, '0.2.0', 'StoreLocatorList\Core\SLList_Query_Helper::add_query_meta()');
    return \StoreLocatorList\Core\SLList_Query_Helper::add_query_meta($wp_query);
}

/**
 * @deprecated 0.2.0 Functionality moved to SLList_Shortcodes class
 */
function make_address($store) {
    _deprecated_function(__FUNCTION__, '0.2.0', 'Moved to SLList_Shortcodes class');
    
    $output = "";
    if (property_exists($store->meta, 'wpsl_address')) {
        $output .= $store->meta->wpsl_address . "<br />";
    }
    if (property_exists($store->meta, 'wpsl_address2')) {
        $output .= $store->meta->wpsl_address2 . "<br />";
    }
    $output .= $store->meta->wpsl_city . ' ' . $store->meta->wpsl_state . ' ' . $store->meta->wpsl_zip;
    return $output;
}