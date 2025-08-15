<?php
/**
 * Temporary Rate Limit Bypass for Testing
 * 
 * Add this to your functions.php or create as a mu-plugin
 * Remember to remove after testing!
 */

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
