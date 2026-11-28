<?php
/**
 * Template Name: Déco Store — عرض كامل (بدون عنوان)
 * Template Post Type: page
 *
 * Edge-to-edge canvas under the site header and above the footer, intended for
 * pages designed in Elementor. The page title is not printed.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="content" <?php post_class( 'ds-main ds-fullwidth' ); ?>>
		<?php the_content(); ?>
	</main>
	<?php
endwhile;

get_footer();
