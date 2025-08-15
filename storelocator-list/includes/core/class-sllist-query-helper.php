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
     * Store search parameters for custom filters
     * @var array
     */
    private static $search_params = array();
    
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
            'post_status' => array('publish', 'pending'),
            'posts_per_page' => $params['posts_per_page'],
            'paged' => $params['paged'],
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        // If we have a search term, build meta query or title search
        if (!empty($params['search_term'])) {
            $search_term = \sanitize_text_field($params['search_term']);
            
            // Check if we're searching in both title and meta fields
            $has_title_search = in_array('name', $params['search_fields']);
            $has_meta_search = (in_array('email', $params['search_fields']) || 
                               in_array('address', $params['search_fields']) || 
                               in_array('phone', $params['search_fields']));
            
            if ($has_title_search && $has_meta_search) {
                // Mixed search: Use a custom query to handle title OR meta
                // We'll use a custom WHERE clause for this
                add_filter('posts_where', array(__CLASS__, 'custom_search_where'), 10, 2);
                add_filter('posts_join', array(__CLASS__, 'custom_search_join'), 10, 2);
                add_filter('posts_groupby', array(__CLASS__, 'custom_search_groupby'), 10, 2);
                
                // Store search parameters for the filter
                self::$search_params = $params;
                
            } else if ($has_title_search) {
                // Title-only search
                $query_args['s'] = $search_term;
                
            } else if ($has_meta_search) {
                // Meta-only search
                $meta_query = array('relation' => 'OR');
                
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
                        'key' => 'wpsl_address2',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    );
                    $meta_query[] = array(
                        'key' => 'wpsl_city',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    );
                    $meta_query[] = array(
                        'key' => 'wpsl_state',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    );
                    $meta_query[] = array(
                        'key' => 'wpsl_zip',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    );
                }
                
                if (in_array('phone', $params['search_fields'])) {
                    // Clean phone number for better matching
                    $clean_search = preg_replace('/[^0-9]/', '', $search_term);
                    $meta_query[] = array(
                        'key' => 'wpsl_phone',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    );
                    if (strlen($clean_search) >= 3) {
                        $meta_query[] = array(
                            'key' => 'wpsl_phone',
                            'value' => $clean_search,
                            'compare' => 'LIKE'
                        );
                    }
                }
                
                // Only add meta_query if we have meta search criteria
                if (count($meta_query) > 1) {
                    $query_args['meta_query'] = $meta_query;
                }
            }
        }
        
        $query = new \WP_Query($query_args);
        
        // Clean up custom search filters if they were used
        remove_filter('posts_where', array(__CLASS__, 'custom_search_where'), 10);
        remove_filter('posts_join', array(__CLASS__, 'custom_search_join'), 10);
        remove_filter('posts_groupby', array(__CLASS__, 'custom_search_groupby'), 10);
        self::$search_params = array();
        
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
    
    /**
     * Custom WHERE clause for mixed title and meta searches
     * 
     * @param string $where WHERE clause
     * @param \WP_Query $query Query object
     * @return string Modified WHERE clause
     */
    public static function custom_search_where($where, $query) {
        global $wpdb;
        
        if (empty(self::$search_params) || !isset(self::$search_params['search_term'])) {
            return $where;
        }
        
        $search_term = \esc_sql(\sanitize_text_field(self::$search_params['search_term']));
        $search_fields = self::$search_params['search_fields'];
        
        $conditions = array();
        
        // Add title search condition
        if (in_array('name', $search_fields)) {
            $conditions[] = "({$wpdb->posts}.post_title LIKE '%{$search_term}%')";
        }
        
        // Add meta search conditions
        if (in_array('email', $search_fields)) {
            $conditions[] = "(mt1.meta_key = 'wpsl_email' AND mt1.meta_value LIKE '%{$search_term}%')";
        }
        
        if (in_array('address', $search_fields)) {
            $conditions[] = "(mt1.meta_key IN ('wpsl_address', 'wpsl_address2', 'wpsl_city', 'wpsl_state', 'wpsl_zip') AND mt1.meta_value LIKE '%{$search_term}%')";
        }
        
        if (in_array('phone', $search_fields)) {
            $clean_search = preg_replace('/[^0-9]/', '', self::$search_params['search_term']);
            $conditions[] = "(mt1.meta_key = 'wpsl_phone' AND (mt1.meta_value LIKE '%{$search_term}%' OR mt1.meta_value LIKE '%{$clean_search}%'))";
        }
        
        if (!empty($conditions)) {
            $where .= " AND (" . implode(' OR ', $conditions) . ")";
        }
        
        return $where;
    }
    
    /**
     * Custom JOIN clause for mixed title and meta searches
     * 
     * @param string $join JOIN clause
     * @param \WP_Query $query Query object
     * @return string Modified JOIN clause
     */
    public static function custom_search_join($join, $query) {
        global $wpdb;
        
        if (empty(self::$search_params)) {
            return $join;
        }
        
        $join .= " LEFT JOIN {$wpdb->postmeta} AS mt1 ON ({$wpdb->posts}.ID = mt1.post_id)";
        
        return $join;
    }
    
    /**
     * Custom GROUP BY clause for mixed title and meta searches
     * 
     * @param string $groupby GROUP BY clause
     * @param \WP_Query $query Query object
     * @return string Modified GROUP BY clause
     */
    public static function custom_search_groupby($groupby, $query) {
        global $wpdb;
        
        if (empty(self::$search_params)) {
            return $groupby;
        }
        
        $groupby = "{$wpdb->posts}.ID";
        
        return $groupby;
    }
}
