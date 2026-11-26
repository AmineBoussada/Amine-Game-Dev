<?php
/**
 * Property search results at /biens/ (post type archive).
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/archive', 'properties' );
get_footer();
