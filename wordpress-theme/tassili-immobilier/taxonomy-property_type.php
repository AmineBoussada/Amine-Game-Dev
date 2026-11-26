<?php
/**
 * Listings of one term (the "property_type" taxonomy). Same screen as the main search results.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/archive', 'properties' );
get_footer();
