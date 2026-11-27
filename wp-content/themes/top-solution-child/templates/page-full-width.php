<?php
/**
 * Template Name: Top Solution — عرض كامل (بدون عنوان)
 * Template Post Type: page
 *
 * Edge-to-edge canvas under the site header and above the footer, intended for
 * pages designed in Elementor. The page title is not printed.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="content" <?php post_class( 'ts-main ts-fullwidth' ); ?>>
		<?php the_content(); ?>
	</main>
	<?php
endwhile;

get_footer();
