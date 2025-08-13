<?php
/**
 * Shortcodes functionality
 * 
 * @package StoreLocator-List
 * @since 0.2.0
 */

namespace StoreLocatorList\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init_shortcodes'));
    }
    
    /**
     * Initialize shortcodes
     */
    public function init_shortcodes() {
        add_shortcode('sllist', array($this, 'sllist_shortcode'));
    }
    
    /**
     * The [sllist] shortcode.
     *
     * Displays a list of stores within a table.
     * 
     * Parameters:
     * category_slug - filter using category
     * show_map=true - display map of store
     * state - filter by state
     * 
     * @param array  $atts    Shortcode attributes. Default empty.
     * @param string $content Shortcode content. Default null.
     * @param string $tag     Shortcode tag (name). Default empty.
     * @return string Shortcode output.
     */
    public function sllist_shortcode($atts = [], $content = null, $tag = "") {
        
        // Normalize attribute keys, lowercase
        $atts = array_change_key_case((array) $atts, CASE_LOWER);

        // Override default attributes with user attributes
        $sllist_atts = shortcode_atts(
            array(
                'category_slug' => null,
                'show_map' => false,
                'state' => false,
            ), $atts, $tag
        );

        // Add the table header
        $content = $this->get_table_header();

        // Get the store data
        $query_args = $this->build_query_args($sllist_atts);
        $store_loop = new \WP_Query($query_args);

        if ($store_loop->have_posts()) {
            // Add metadata to the post
            $store_loop = SLList_Query_Helper::add_query_meta($store_loop);

            while ($store_loop->have_posts()) {
                // Using next_post like this pulls your posts out for easy access
                $store = $store_loop->next_post();
                $content .= $this->render_store_row($store, $sllist_atts);
            }
        }

        // Complete the table
        $content .= "</tbody></table></figure>";
        
        // Reset post data
        wp_reset_postdata();
        
        return $content;
    }
    
    /**
     * Get table header HTML
     * 
     * @return string
     */
    private function get_table_header() {
        return <<<HTML
        <figure class="wp-block-table" style="width:100%">
        <table>
            <thead>
                <tr>
                    <th style="width:25%">Location</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
        HTML;
    }
    
    /**
     * Build query arguments for store lookup
     * 
     * @param array $atts Shortcode attributes
     * @return array Query arguments
     */
    private function build_query_args($atts) {
        $query_args = array(
            'post_type' => 'wpsl_stores',
            'post_status' => array('pending', 'publish'),
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_key' => 'wpsl_city',
            'nopaging' => true,
        );
        
        // Add store category filter if provided
        if ($atts['category_slug'] !== null) {
            $tax_query = array(
                'taxonomy' => 'wpsl_store_category',
                'field' => 'slug',
                'terms' => $atts['category_slug'],
            );
            $query_args['tax_query'] = array($tax_query);
        }

        // Add state filter if provided
        if ($atts['state'] !== null && $atts['state'] !== false) {
            $meta_query = array(
                'key' => 'wpsl_state',
                'value' => $atts['state'],
            );
            $query_args['meta_query'] = array($meta_query);
        }
        
        return $query_args;
    }
    
    /**
     * Render a single store row
     * 
     * @param object $store Store post object
     * @param array $atts Shortcode attributes
     * @return string HTML for store row
     */
    private function render_store_row($store, $atts) {
        $content = "<tr><td><b>{$store->meta->wpsl_city}</b></td><td>";
        
        // Store name with optional link
        if (property_exists($store->meta, 'wpsl_url') && !empty($store->meta->wpsl_url)) {
            $content .= "<a href='{$store->meta->wpsl_url}'><b>{$store->post_title}</b> <i class='fa-solid fa-arrow-up-right-from-square'></i></a>";
        } else {
            $content .= "<b>{$store->post_title}</b>";
        }

        // Address
        $address = $this->make_address($store);
        $content .= "<br />{$address}";

        // Phone
        if (property_exists($store->meta, 'wpsl_phone') && !empty($store->meta->wpsl_phone)) {
            $content .= "<br /><i class='fa-solid fa-phone'></i> <a href='tel:{$store->meta->wpsl_phone}'>{$store->meta->wpsl_phone}</a>";
        }

        // Email
        if (property_exists($store->meta, 'wpsl_email') && !empty($store->meta->wpsl_email)) {
            $content .= "<br /><i class='fa-solid fa-envelope'></i> <a href='mailto:{$store->meta->wpsl_email}'>{$store->meta->wpsl_email}</a>";
        }

        // Description
        $content .= "<br />{$store->post_content}";

        // Optional map
        if ($atts['show_map']) {
            $content .= "<br />" . do_shortcode("[wpsl_map id='{$store->ID}' width='400' height='150']");
        }
        
        $content .= "</td></tr>";
        
        return $content;
    }
    
    /**
     * Create formatted address string
     * 
     * @param object $store Store post object
     * @return string Formatted address
     */
    private function make_address($store) {
        $output = "";
        
        if (property_exists($store->meta, 'wpsl_address') && !empty($store->meta->wpsl_address)) {
            $output .= esc_html($store->meta->wpsl_address) . "<br />";
        }
        
        if (property_exists($store->meta, 'wpsl_address2') && !empty($store->meta->wpsl_address2)) {
            $output .= esc_html($store->meta->wpsl_address2) . "<br />";
        }
        
        $city_state_zip = '';
        if (property_exists($store->meta, 'wpsl_city') && !empty($store->meta->wpsl_city)) {
            $city_state_zip .= esc_html($store->meta->wpsl_city);
        }
        
        if (property_exists($store->meta, 'wpsl_state') && !empty($store->meta->wpsl_state)) {
            $city_state_zip .= ' ' . esc_html($store->meta->wpsl_state);
        }
        
        if (property_exists($store->meta, 'wpsl_zip') && !empty($store->meta->wpsl_zip)) {
            $city_state_zip .= ' ' . esc_html($store->meta->wpsl_zip);
        }
        
        if (!empty($city_state_zip)) {
            $output .= $city_state_zip;
        }
        
        return $output;
    }
}
