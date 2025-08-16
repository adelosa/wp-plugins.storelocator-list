<?php
/**
 * Simple Store Search Test
 * 
 * This file contains code to test the store search functionality.
 * 
 * USAGE:
 * Add this code to your theme's functions.php file temporarily to test
 * the store search functionality, then visit any page with ?sllist_page=store_manager
 * 
 * EXAMPLE: http://localhost:8080/?sllist_page=store_manager
 * 
 * Remember to remove from functions.php after testing!
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('This file should not be accessed directly. Include the code in functions.php.');
}

// Test function to check if our query var is working
function sllist_test_query_var() {
    if (isset($_GET['sllist_page']) && $_GET['sllist_page'] === 'store_manager') {
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; margin: 20px 0;">';
        echo '<h3>✅ Store Search Test Results</h3>';
        echo '<p><strong>Query parameter detected:</strong> sllist_page = ' . sanitize_text_field($_GET['sllist_page']) . '</p>';
        
        // Check if our classes exist
        if (class_exists('StoreLocatorList\\PublicPages\\SLList_Store_Search')) {
            echo '<p><strong>Store Search class:</strong> ✅ Available</p>';
            
            // Try to create instance and show search form
            try {
                echo '<hr>';
                echo '<h4>Store Search Form Test:</h4>';
                
                // Basic search form
                echo '<form method="get" style="background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px;">';
                echo '<input type="hidden" name="sllist_page" value="store_manager">';
                echo '<div style="margin-bottom: 10px;">';
                echo '<label for="search_term"><strong>Search for your store:</strong></label><br>';
                echo '<input type="text" name="search_term" id="search_term" style="width: 300px; padding: 8px; margin-top: 5px;" placeholder="Enter store name, address, email, or phone">';
                echo '</div>';
                echo '<div style="margin-bottom: 10px;">';
                echo '<label><input type="checkbox" name="search_fields[]" value="name" checked> Store Name</label> ';
                echo '<label><input type="checkbox" name="search_fields[]" value="address" checked> Address</label> ';
                echo '<label><input type="checkbox" name="search_fields[]" value="email" checked> Email</label> ';
                echo '<label><input type="checkbox" name="search_fields[]" value="phone" checked> Phone</label>';
                echo '</div>';
                echo '<button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer;">Search Stores</button>';
                echo '</form>';
                
                // If search was performed
                if (!empty($_GET['search_term'])) {
                    echo '<hr>';
                    echo '<h4>Search Results:</h4>';
                    
                    $search_term = sanitize_text_field($_GET['search_term']);
                    $search_fields = isset($_GET['search_fields']) ? array_map('sanitize_text_field', $_GET['search_fields']) : ['name'];
                    
                    echo '<p><strong>Searching for:</strong> "' . esc_html($search_term) . '"</p>';
                    echo '<p><strong>In fields:</strong> ' . implode(', ', $search_fields) . '</p>';
                    
                    // Simple store search using WordPress query
                    $args = array(
                        'post_type' => 'wpsl_stores',
                        'post_status' => 'publish',
                        'posts_per_page' => 10,
                        'meta_query' => array('relation' => 'OR')
                    );
                    
                    foreach ($search_fields as $field) {
                        switch ($field) {
                            case 'name':
                                $args['s'] = $search_term;
                                break;
                            case 'email':
                                $args['meta_query'][] = array(
                                    'key' => 'wpsl_email',
                                    'value' => $search_term,
                                    'compare' => 'LIKE'
                                );
                                break;
                            case 'address':
                                $args['meta_query'][] = array(
                                    'key' => 'wpsl_address',
                                    'value' => $search_term,
                                    'compare' => 'LIKE'
                                );
                                break;
                            case 'phone':
                                $args['meta_query'][] = array(
                                    'key' => 'wpsl_phone',
                                    'value' => $search_term,
                                    'compare' => 'LIKE'
                                );
                                break;
                        }
                    }
                    
                    $stores = get_posts($args);
                    
                    if (!empty($stores)) {
                        echo '<p><strong>Found ' . count($stores) . ' store(s):</strong></p>';
                        echo '<div style="display: grid; gap: 15px;">';
                        
                        foreach ($stores as $store) {
                            $email = get_post_meta($store->ID, 'wpsl_email', true);
                            $phone = get_post_meta($store->ID, 'wpsl_phone', true);
                            $address = get_post_meta($store->ID, 'wpsl_address', true);
                            $city = get_post_meta($store->ID, 'wpsl_city', true);
                            $state = get_post_meta($store->ID, 'wpsl_state', true);
                            $zip = get_post_meta($store->ID, 'wpsl_zip', true);
                            
                            echo '<div style="border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; background: white;">';
                            echo '<h5 style="margin: 0 0 10px 0; color: #007cba;">' . esc_html($store->post_title) . '</h5>';
                            
                            if ($address) {
                                echo '<p style="margin: 5px 0;"><strong>📍 Address:</strong> ';
                                echo esc_html($address);
                                if ($city) echo ', ' . esc_html($city);
                                if ($state) echo ', ' . esc_html($state);
                                if ($zip) echo ' ' . esc_html($zip);
                                echo '</p>';
                            }
                            
                            if ($email) {
                                echo '<p style="margin: 5px 0;"><strong>✉️ Email:</strong> ' . esc_html($email) . '</p>';
                            }
                            
                            if ($phone) {
                                echo '<p style="margin: 5px 0;"><strong>📞 Phone:</strong> ' . esc_html($phone) . '</p>';
                            }
                            
                            if ($store->post_content) {
                                $excerpt = wp_trim_words($store->post_content, 20);
                                echo '<p style="margin: 5px 0; color: #6c757d;"><em>' . esc_html($excerpt) . '</em></p>';
                            }
                            
                            echo '<button onclick="alert(\'This would request access for store ID: ' . $store->ID . '\')" style="background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 3px; cursor: pointer; margin-top: 10px;">Request Access to Update</button>';
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<p style="color: #dc3545;"><strong>No stores found matching your search.</strong></p>';
                        
                        // Show total stores for debugging
                        $total_stores = wp_count_posts('wpsl_stores');
                        echo '<p><em>Total stores in database: ' . $total_stores->publish . '</em></p>';
                    }
                }
                
            } catch (Exception $e) {
                echo '<p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p><strong>Store Search class:</strong> ❌ Not available</p>';
        }
        
        echo '</div>';
    }
}

// Hook it to wp_head so it shows at the top of pages
add_action('wp_head', 'sllist_test_query_var');

// Also add a debug notice in admin
function sllist_debug_admin_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Store Search Debug:</strong> Test the store search by visiting: ';
        echo '<a href="' . home_url('/?sllist_page=store_manager') . '" target="_blank">' . home_url('/?sllist_page=store_manager') . '</a>';
        echo '</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'sllist_debug_admin_notice');
