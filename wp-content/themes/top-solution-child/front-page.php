<?php
/**
 * Front page. If the page assigned as homepage is built with Elementor its
 * content is rendered; otherwise the built-in homepage sections are shown.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() && ts_child_is_elementor_built( get_queried_object_id() ) ) {
	while ( have_posts() ) {
		the_post();
		echo '<main id="content" class="ts-main ts-home ts-home--elementor">';
		the_content();
		echo '</main>';
	}
} else {
	get_template_part( 'template-parts/home/home' );
}

get_footer();
