<?php
/**
 * @package StoreLocator-List
 * @version 0.1.0
 */
/*
Plugin Name: WP Store Locator List
Plugin URI: http://bankteksystems.com.au/
Description: Provides ability to add list of stores via shortcode
Author: Anthony Delosa
Version: 0.1.0
*/

function sllist_shortcodes_init() {
	add_shortcode( 'sllist', 'sllist_shortcode' );
}

function sllist_register_style() {
	$block_style_deps = array();

	if( function_exists('wp_should_load_separate_core_block_assets') ) {
		if( wp_should_load_separate_core_block_assets() === true ) {
			$block_style_deps[] = "wp-block-table";
		}
	}
	wp_register_style('sllist_css', false, $block_style_deps, null);
	wp_enqueue_style('sllist_css');	
}

add_action( 'wp_enqueue_scripts', 'sllist_register_style');
add_action( 'init', 'sllist_shortcodes_init' );

function make_address($store) {
	$output = "";
	$output .= $store->meta->wpsl_address . "<br \>";
	if (property_exists($store->meta, 'wpsl_address2')) {
		$output .= $store->meta->wpsl_address2 . "<br \>";
	}
	$output .= $store->meta->wpsl_city . ' ' . $store->meta->wpsl_state . ' ' . $store->meta->wpsl_zip;
	return $output;
}

/**
 * The [sllist] shortcode.
 *
 * Displays a list of stores within a table.
 * 
 * Parameters:
 * category_slug - filter using category
 * show_map=true - display map of store
 * 
 * @param array  $atts    Shortcode attributes. Default empty.
 * @param string $content Shortcode content. Default null.
 * @param string $tag     Shortcode tag (name). Default empty.
 * @return string Shortcode output.
 */
function sllist_shortcode( $atts = [], $content = null, $tag = "" ) {

    // normalize attribute keys, lowercase
    $atts = array_change_key_case( (array) $atts, CASE_LOWER );

    // override default attributes with user attributes
    $sllist_atts = shortcode_atts(
        array(
            'category_slug' => null,
			'show_map' => False,
        ), $atts, $tag
    );

    // add the table header
    $content = <<<HTML
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

	// Get the store data
	$query_args = array(
        'post_type' => 'wpsl_stores',
        'post_status' => array( 'pending', 'publish' ),
		'orderby' => 'meta_value',
		'order' => 'ASC',
		'meta_key' => 'wpsl_city',
	);
	
	// add store category filter if provided
	$store_category = $sllist_atts['category_slug']; 
	if ($store_category != null) {
		$tax_query = array(
			'taxonomy' => 'wpsl_store_category',
			'field' => 'slug',
			'terms' => $store_category,
		);
		$query_args['tax_query'] = array($tax_query);
	}
    
	$store_loop = new WP_Query( $query_args );

    if ( $store_loop->have_posts() ) {

		// Adds metadata to the post
		$store_loop = sllist_add_query_meta($store_loop);

        while ( $store_loop->have_posts() ) {

            // using next_post like this pulls your posts out for easy access
            $store = $store_loop->next_post();

			// Add content
            $content .= <<<HTML
            <tr>
                <td><b>{$store->meta->wpsl_city}</b></td>
                <td>
                    <b>{$store->post_title}</b>
            HTML;

            if (property_exists($store->meta, 'wpsl_url')) {
                $content .=	"<br /><a href='{$store->meta->wpsl_url}'>Class website</a>";
            }
			$address = make_address($store);
            $content .= "<br />{$address}";

			if (property_exists($store->meta, 'wpsl_phone')) {
                $content .= "<br /><i class='fa-solid fa-phone'></i> <a href='tel:{$store->meta->wpsl_phone}'>{$store->meta->wpsl_phone}</a>";
			}

			if (property_exists($store->meta, 'wpsl_email')) {
                $content .= "<br /><i class='fa-solid fa-envelope'></i> <a href='mailto:{$store->meta->wpsl_email}'>{$store->meta->wpsl_email}</a>";
            }

            $content .= "<br />{$store->post_content}";

			if ($sllist_atts['show_map']) {
				$content .= "<br />" . do_shortcode("[wpsl_map id='{$store->ID}' width='400' height='150']");
			}
            $content .= "</td></tr>";
        }
    }

    # complete the table
    $content .= "</tbody></table></figure>";
    return $content;
    
}

function sllist_add_query_meta($wp_query = "") {
	/*
	Adds post metadata to a query object
	Adapted from https://wordpress.stackexchange.com/questions/172041/can-wp-query-return-posts-meta-in-a-single-request
	*/

	// return In case if wp_query is empty or postmeta already exist
	if( (empty($wp_query)) || (!empty($wp_query) && !empty($wp_query->posts) && isset($wp_query->posts[0]->postmeta)) ) { return $wp_query; }

	$sql = $postmeta = '';
	$post_ids = array();
	$post_ids = wp_list_pluck( $wp_query->posts, 'ID' );
	if(!empty($post_ids)) {
		global $wpdb;
		$post_ids = implode(',', $post_ids);
		$sql = "SELECT meta_key, meta_value, post_id FROM $wpdb->postmeta WHERE post_id IN ($post_ids)";
		$postmeta = $wpdb->get_results($sql, OBJECT);
		if(!empty($postmeta)) {
			foreach($wp_query->posts as $pKey => $pVal) {
				$current_post_metadata = new StdClass();
				foreach($postmeta as $mKey => $mVal) {
					if($postmeta[$mKey]->post_id == $wp_query->posts[$pKey]->ID) {
						$newmeta[$mKey] = new stdClass();
						$newmeta[$mKey]->meta_key = $postmeta[$mKey]->meta_key;
						$newmeta[$mKey]->meta_value = maybe_unserialize($postmeta[$mKey]->meta_value);
						$current_post_metadata = (object) array_merge((array) $current_post_metadata, (array) $newmeta);
						unset($newmeta);
					}		
				}
				$meta = array();
				foreach($current_post_metadata as $k => $v) {
					$meta[$v->meta_key] = $v->meta_value;
				}
				// Add the metadata to the query object
				$wp_query->posts[$pKey]->meta = (object)$meta; 
			}
		}
		unset($post_ids); unset($sql); unset($postmeta);
	}
	return $wp_query;
}

?>