<?php
/**
 * @package StoreLocator-List
 * @version 0.2.0
 */
/*
Plugin Name: WP Store Locator List
Plugin URI: https://github.com/adelosa/wp-plugins.storelocator-list
Description: Provides ability to add list of stores via shortcode with self-service store owner updates
Author: Anthony Delosa
Version: 0.2.0
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
define('SLLIST_VERSION', '0.2.0');
define('SLLIST_PLUGIN_FILE', __FILE__);
define('SLLIST_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SLLIST_PLUGIN_URL', plugin_dir_url(__FILE__));

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
 * Legacy function support for backward compatibility
 * These functions are deprecated and will be removed in a future version
 */

/**
 * @deprecated 0.2.0 Use StoreLocatorList\Core\SLList_Shortcodes::sllist_shortcode() instead
 */
function sllist_shortcode($atts = [], $content = null, $tag = "") {
    _deprecated_function(__FUNCTION__, '0.2.0', 'StoreLocatorList\Core\SLList_Shortcodes::sllist_shortcode()');
    
    // Ensure the new system is loaded
    if (!class_exists('StoreLocatorList\Core\SLList_Shortcodes')) {
        sllist_init_plugin();
    }
    
    $shortcodes = new \StoreLocatorList\Core\SLList_Shortcodes();
    return $shortcodes->sllist_shortcode($atts, $content, $tag);
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