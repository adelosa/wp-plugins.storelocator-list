<?php
/**
 * Temporary Rate Limit Bypass for Testing
 * 
 * This file contains code snippets to disable rate limiting during testing.
 * 
 * USAGE OPTIONS:
 * 1. Add this content to your theme's functions.php file
 * 2. Create as a mu-plugin (wp-content/mu-plugins/disable-rate-limit.php)
 * 3. Include in a test environment setup
 * 
 * WARNING: Remember to remove after testing! Do not use in production.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('This file should not be accessed directly. Include the code in functions.php or as a mu-plugin.');
}

// Bypass rate limiting for testing
add_filter('sllist_bypass_rate_limit', '__return_true');

// Or bypass only for admins
add_filter('sllist_bypass_rate_limit', function() {
    return current_user_can('manage_options');
});

// Alternative: Increase rate limits dramatically for testing
add_filter('sllist_rate_limits', function($limits) {
    $limits['store_search'] = 1000; // 1000 requests per hour
    $limits['access_request'] = 1000; // 1000 requests per hour
    return $limits;
});
?>
