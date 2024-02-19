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


function sllist_shortcode( $atts = [], $content = null, $tag = "" ) {

	// normalize attribute keys, lowercase
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );

	// override default attributes with user attributes
	$sllist_atts = shortcode_atts(
		array(
			'term_id' => null,
		), $atts, $tag
	);

	$data = sllist_db_query($sllist_atts['term_id']);
	
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
		EOD;
		
		if ($store->wpsl_email != "") {
			$content .= "<br /><i class='fa-solid fa-envelope'></i> <a href='mailto:$store->wpsl_email'>$store->wpsl_email</a>";
		}

		$content .= <<<EOD
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

function sllist_db_query($term_id = null) {

	global $wpdb;

	$join_sql = "";
	if ($term_id != null) {
		$join_sql = <<<EOD
		join wp_term_relationships
		on wp_term_relationships.object_id = p.ID
		EOD;
	}

	$where_sql = "";
	if ($term_id != null) {
		$where_sql .= "and wp_term_relationships.term_taxonomy_id = $term_id";
	}

	$query_string = <<<EOD
	SELECT 
	p.*,
	max(IF(m.meta_key='wpsl_address',m.meta_value,"")) as wpsl_address,
	max(IF(m.meta_key='wpsl_address2',m.meta_value,"")) as wpsl_address2,
	max(IF(m.meta_key='wpsl_city',m.meta_value,"")) as wpsl_city,
	max(IF(m.meta_key='wpsl_state',m.meta_value,"")) as wpsl_state,
	max(IF(m.meta_key='wpsl_zip',m.meta_value,"")) as wpsl_zip,
	max(IF(m.meta_key='wpsl_country',m.meta_value,"")) as wpsl_country,
	max(IF(m.meta_key='wpsl_phone',m.meta_value,"")) as wpsl_phone,
	max(IF(m.meta_key='wpsl_email',m.meta_value,"")) as wpsl_email,
	max(IF(m.meta_key='wpsl_url',m.meta_value,"")) as wpsl_url
	FROM wp_postmeta m
	INNER JOIN wp_posts p
	ON m.post_id = p.id
	$join_sql
	WHERE p.post_type = 'wpsl_stores'
	AND p.post_status in ('publish', 'pending')
	$where_sql
	GROUP BY m.post_id
	ORDER by wpsl_city asc;
	EOD;

	$results = $wpdb->get_results($query_string);

	return $results;
}

?>