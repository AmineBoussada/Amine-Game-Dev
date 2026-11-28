<?php
/**
 * Fallback template. Replaces the parent's index.php so that pages, posts,
 * archives, search and 404 are always rendered by this theme's own template
 * parts (the parent version also checks Elementor Pro Theme Builder locations,
 * which this theme does not use).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_singular() ) {
	get_template_part( 'template-parts/single' );
} elseif ( is_search() ) {
	get_template_part( 'template-parts/search' );
} elseif ( is_archive() || is_home() ) {
	get_template_part( 'template-parts/archive' );
} else {
	get_template_part( 'template-parts/404' );
}

get_footer();
