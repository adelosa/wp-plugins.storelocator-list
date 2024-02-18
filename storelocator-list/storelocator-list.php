<?php
/**
 * @package StoreLocator-List
 * @version 0.0.1
 */
/*
Plugin Name: StoreLocator List Shortcode
Plugin URI: http://bankteksystems.com.au/
Description: Provides ability to add list of stores via shortcode
Author: Anthony Delosa
Version: 0.0.1
*/

/**
 * The [sllist] shortcode.
 *
 * Accepts a title and will display a box.
 *
 * @param array  $atts    Shortcode attributes. Default empty.
 * @param string $content Shortcode content. Default null.
 * @param string $tag     Shortcode tag (name). Default empty.
 * @return string Shortcode output.
 */

$block_style_deps = array();

if( function_exists('wp_should_load_separate_core_block_assets') ) {
    if( wp_should_load_separate_core_block_assets() === true ) {
        $my_block_style_deps[] = "wp-block-table";
    }
}

wp_register_style(
  'sllist_css',
   FALSE,
   $block_style_deps,
   NULL
);


function sllist_shortcode( $atts = [], $content = null ) {
	
	$data = sllist_db_query(3);
	
	# add the table header
	$content = <<<EOD
		<figure class="wp-block-table" style="width:100%">
		<table>
			<thead>
				<tr>
					<th style="width:25%">Location</th>
					<th>Details</th>
				</tr>
			</thead>
			<tbody>
		EOD;

	# add the rows
	foreach($data as $store) {
		$address = "??";
		$address = make_address($store);
		
		$content .= <<<EOD
		<tr>
			<td><b>$store->wpsl_city</b></td>
			<td>
			   <b>$store->post_title</b>
		EOD;

		// <button onclick="window.location.href='/?post_type=wpsl_stores&p=$store->ID';">More</button>

		if ($store->wpsl_url != NULL) {
			$content .=	"<br /><a href='$store->wpsl_url'>Class website</a>";
		}
		
		$content .= <<<EOD
			<br />$address
			<br /><i class="fa-solid fa-phone"></i> <a href="tel:$store->wpsl_phone">$store->wpsl_phone</a>
			<br /><i class="fa-solid fa-envelope"></i> <a href="mailto:$store->wpsl_email">$store->wpsl_email</a>
			<br />$store->post_content</td>
		</tr>
		EOD;
	}

	# complete the table
	$content .= "</tbody></table></figure>";
	return $content;
}

/**
 * Central location to create all shortcodes.
 */
function sllist_shortcodes_init() {
	add_shortcode( 'sllist', 'sllist_shortcode' );
}

add_action( 'init', 'sllist_shortcodes_init' );

function make_address($store) {
	$output = "";
	$output .= $store->wpsl_address . "<br \>";
	if ($store->wpsl_address2 != NULL) {
		$output .= $store->wpsl_address2 . "<br \>";
	}
	$output .= $store->wpsl_city . ' ' . $store->wpsl_state . ' ' . $store->wpsl_zip;
	return $output;
}

function sllist_db_query($term_id = NULL) {
	print("term_id=$term_id");
	global $wpdb;

	$join_sql = "";
	if ($term_id != NULL) {	
		$join_sql = <<<EOD
		join `wp_term_relationships`
		on `wp_term_relationships`.`object_id` = `wp_posts`.`ID`
		join `wp_terms`
		on `wp_terms`.`term_id` = `wp_term_relationships`.`term_taxonomy_id`
		EOD;
	}

	$where_sql = "where wp_posts.post_status in ('publish', 'pending')";
	if ($term_id != NULL) {
		$where_sql .= "and `wp_terms`.`term_id` = $term_id";
	}

	$query_string = <<<EOD
	select 
	* 
	from 
	wp_posts
	join (
	SELECT 
		post_id, 
		max(wpsl_address) as wpsl_address,
		max(wpsl_address2) as wpsl_address2,
		max(wpsl_city) as wpsl_city,
		max(wpsl_state) as wpsl_state,
		max(wpsl_zip) as wpsl_zip,
		max(wpsl_country) as wpsl_country,
		max(wpsl_phone) as wpsl_phone,
		max(wpsl_email) as wpsl_email,
		max(wpsl_url) as wpsl_url
		FROM (
			SELECT
			post_id,
			CASE when meta_key = "wpsl_address" then meta_value end as wpsl_address,
			CASE when meta_key = "wpsl_address2" then meta_value end as wpsl_address2,
			CASE when meta_key = "wpsl_city" then meta_value end as wpsl_city,
			CASE when meta_key = "wpsl_state" then meta_value end as wpsl_state,
			CASE when meta_key = "wpsl_zip" then meta_value end as wpsl_zip,
			CASE when meta_key = "wpsl_country" then meta_value end as wpsl_country,
			CASE when meta_key = "wpsl_phone" then meta_value end as wpsl_phone,
			CASE when meta_key = "wpsl_email" then meta_value end as wpsl_email,
			CASE when meta_key = "wpsl_url" then meta_value end as wpsl_url
			FROM wp_postmeta
			WHERE 
			LEFT(meta_key,5) = 'wpsl_'
		) as a
		GROUP by post_id
	) as a 
	on a.post_id = ID
	$join_sql
	$where_sql
	order by a.wpsl_city asc;
	EOD;
	print("query=$query_string");
	$results = $wpdb->get_results($query_string);
	return $results;
}

?>