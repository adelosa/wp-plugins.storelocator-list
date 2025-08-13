<?php
/**
 * Query Helper functionality
 * 
 * @package StoreLocator-List
 * @since 0.2.0
 */

namespace StoreLocatorList\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Query_Helper {
    
    /**
     * Add post metadata to a query object
     * 
     * Adapted from https://wordpress.stackexchange.com/questions/172041/can-wp-query-return-posts-meta-in-a-single-request
     * 
     * @param \WP_Query $wp_query The query object to enhance
     * @return \WP_Query Enhanced query object with metadata
     */
    public static function add_query_meta($wp_query = "") {
        
        // Return if wp_query is empty or postmeta already exists
        if ((empty($wp_query)) || (!empty($wp_query) && !empty($wp_query->posts) && isset($wp_query->posts[0]->postmeta))) {
            return $wp_query;
        }

        $sql = $postmeta = '';
        $post_ids = array();
        $post_ids = wp_list_pluck($wp_query->posts, 'ID');
        
        if (!empty($post_ids)) {
            global $wpdb;
            $post_ids = implode(',', array_map('intval', $post_ids)); // Sanitize IDs
            $sql = $wpdb->prepare(
                "SELECT meta_key, meta_value, post_id FROM {$wpdb->postmeta} WHERE post_id IN (%s)",
                $post_ids
            );
            // Note: We can't use $wpdb->prepare with IN clause directly, but we've sanitized above
            $sql = "SELECT meta_key, meta_value, post_id FROM {$wpdb->postmeta} WHERE post_id IN ($post_ids)";
            $postmeta = $wpdb->get_results($sql, OBJECT);
            
            if (!empty($postmeta)) {
                foreach ($wp_query->posts as $pKey => $pVal) {
                    $current_post_metadata = new \StdClass();
                    
                    foreach ($postmeta as $mKey => $mVal) {
                        if ($postmeta[$mKey]->post_id == $wp_query->posts[$pKey]->ID) {
                            $newmeta[$mKey] = new \stdClass();
                            $newmeta[$mKey]->meta_key = $postmeta[$mKey]->meta_key;
                            $newmeta[$mKey]->meta_value = maybe_unserialize($postmeta[$mKey]->meta_value);
                            $current_post_metadata = (object) array_merge((array) $current_post_metadata, (array) $newmeta);
                            unset($newmeta);
                        }
                    }
                    
                    $meta = array();
                    foreach ($current_post_metadata as $k => $v) {
                        $meta[$v->meta_key] = $v->meta_value;
                    }
                    
                    // Add the metadata to the query object
                    $wp_query->posts[$pKey]->meta = (object) $meta;
                }
            }
            
            unset($post_ids);
            unset($sql);
            unset($postmeta);
        }
        
        return $wp_query;
    }
    
    /**
     * Search for stores by various criteria
     * 
     * @param array $search_params Search parameters
     * @return \WP_Query Query results
     */
    public static function search_stores($search_params = array()) {
        $defaults = array(
            'search_term' => '',
            'search_fields' => array('name', 'email', 'address', 'phone'),
            'posts_per_page' => 10,
            'paged' => 1,
        );
        
        $params = wp_parse_args($search_params, $defaults);
        
        $query_args = array(
            'post_type' => 'wpsl_stores',
            'post_status' => array('pending', 'publish'),
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['paged'],
        );
        
        // If we have a search term, build meta query or title search
        if (!empty($params['search_term'])) {
            $meta_query = array('relation' => 'OR');
            $search_term = sanitize_text_field($params['search_term']);
            
            // Search in post title (store name)
            if (in_array('name', $params['search_fields'])) {
                $query_args['s'] = $search_term;
            }
            
            // Search in meta fields
            if (in_array('email', $params['search_fields'])) {
                $meta_query[] = array(
                    'key' => 'wpsl_email',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            if (in_array('address', $params['search_fields'])) {
                $meta_query[] = array(
                    'key' => 'wpsl_address',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
                $meta_query[] = array(
                    'key' => 'wpsl_city',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            if (in_array('phone', $params['search_fields'])) {
                $meta_query[] = array(
                    'key' => 'wpsl_phone',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                );
            }
            
            if (count($meta_query) > 1) {
                $query_args['meta_query'] = $meta_query;
            }
        }
        
        $query = new \WP_Query($query_args);
        return self::add_query_meta($query);
    }
    
    /**
     * Get store by ID with metadata
     * 
     * @param int $store_id Store post ID
     * @return object|false Store object with metadata or false if not found
     */
    public static function get_store_by_id($store_id) {
        $store_id = intval($store_id);
        
        if ($store_id <= 0) {
            return false;
        }
        
        $post = get_post($store_id);
        
        if (!$post || $post->post_type !== 'wpsl_stores') {
            return false;
        }
        
        // Get all meta data
        $meta_data = get_post_meta($store_id);
        $meta = new \stdClass();
        
        foreach ($meta_data as $key => $value) {
            $meta->$key = maybe_unserialize($value[0]);
        }
        
        $post->meta = $meta;
        
        return $post;
    }
}
