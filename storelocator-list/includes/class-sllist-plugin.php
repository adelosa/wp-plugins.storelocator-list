<?php
/**
 * Main Plugin Class
 * 
 * @package StoreLocator-List
 * @since 0.2.0
 */

namespace StoreLocatorList;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Plugin {
    
    /**
     * Plugin version
     */
    const VERSION = '0.2.1';
    
    /**
     * Plugin instance
     * @var SLList_Plugin
     */
    private static $instance = null;
    
    /**
     * Plugin path
     * @var string
     */
    private $plugin_path;
    
    /**
     * Plugin URL
     * @var string
     */
    private $plugin_url;
    
    /**
     * Get instance
     * 
     * @return SLList_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path(dirname(__FILE__));
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));
        
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Note: query_vars and rewrite rules are now handled at the plugin level for early registration
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Cleanup cron job
        add_action('sllist_cleanup_expired_tokens', array($this, 'cleanup_expired_tokens'));
        
        // Activation and deactivation hooks
        register_activation_hook($this->plugin_path . 'storelocator-list.php', array($this, 'activate'));
        register_deactivation_hook($this->plugin_path . 'storelocator-list.php', array($this, 'deactivate'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Core classes
        require_once $this->plugin_path . 'includes/core/class-sllist-shortcodes.php';
        require_once $this->plugin_path . 'includes/core/class-sllist-query-helper.php';
        require_once $this->plugin_path . 'includes/core/class-sllist-security.php';
        
        // Public classes
        require_once $this->plugin_path . 'includes/public/class-sllist-store-search.php';
        require_once $this->plugin_path . 'includes/public/class-sllist-store-update.php';
        
        // Admin classes
        if (is_admin()) {
            require_once $this->plugin_path . 'includes/admin/class-sllist-import-export.php';
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('storelocator-list', false, dirname(plugin_basename($this->plugin_path . 'storelocator-list.php')) . '/languages');
        
        // Note: Rewrite rules are now handled at the plugin level for early registration
        
        // Initialize core components
        new Core\SLList_Shortcodes();
        
        // Initialize public components
        new PublicPages\SLList_Store_Search();
        new PublicPages\SLList_Store_Update();
        
        // Initialize admin components
        if (is_admin()) {
            new Admin\SLList_Import_Export();
        }
        
        // Check if we need to flush rewrite rules
        if (get_option('sllist_flush_rewrite_rules', false)) {
            flush_rewrite_rules(true);
            delete_option('sllist_flush_rewrite_rules');
        }
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        $block_style_deps = array();

        if (function_exists('wp_should_load_separate_core_block_assets')) {
            if (wp_should_load_separate_core_block_assets() === true) {
                $block_style_deps[] = "wp-block-table";
            }
        }
        
        // Enqueue our custom styles
        wp_enqueue_style(
            'sllist-styles',
            $this->plugin_url . 'assets/css/sllist-styles.css',
            $block_style_deps,
            self::VERSION
        );
        
        // Legacy support - register empty style for backward compatibility
        wp_register_style('sllist_css', false, $block_style_deps, self::VERSION);
        wp_enqueue_style('sllist_css');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Add activation logic here
        // For example: create database tables, set default options, etc.
        
        // Schedule cleanup cron job
        if (!wp_next_scheduled('sllist_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'daily', 'sllist_cleanup_expired_tokens');
        }
        
        // Ensure rewrite rules are added (they're now handled at plugin level)
        // Call the functions directly to ensure they're registered
        sllist_add_rewrite_rules();
        
        // Force flush rewrite rules to ensure our custom pages work
        flush_rewrite_rules(true);
        
        // Also set a flag to flush again on next init (just in case)
        update_option('sllist_flush_rewrite_rules', true);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Add deactivation logic here
        // Clean up temporary data, but keep user data
        
        // Clear scheduled cleanup
        wp_clear_scheduled_hook('sllist_cleanup_expired_tokens');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Cleanup expired tokens (cron job callback)
     */
    public function cleanup_expired_tokens() {
        $cleaned_count = Core\SLList_Security::cleanup_expired_tokens();
        
        // Log cleanup activity
        error_log("SLList: Cleaned up {$cleaned_count} expired tokens");
        
        // Optional: Store cleanup statistics
        $stats = get_option('sllist_cleanup_stats', array());
        $stats[date('Y-m-d')] = $cleaned_count;
        
        // Keep only last 30 days of stats
        $stats = array_slice($stats, -30, 30, true);
        update_option('sllist_cleanup_stats', $stats);
    }
    
    /**
     * Get plugin path
     * 
     * @return string
     */
    public function get_plugin_path() {
        return $this->plugin_path;
    }
    
    /**
     * Get plugin URL
     * 
     * @return string
     */
    public function get_plugin_url() {
        return $this->plugin_url;
    }
}
