<?php
/**
 * Template Name: Top Solution — اتصل بنا
 * Template Post Type: page
 *
 * Contact page: the page content (editable in the block editor or Elementor
 * Free) followed by contact cards built from Appearance > Customize >
 * Top Solution > Contact data.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="content" <?php post_class( 'ts-main ts-page ts-page--contact' ); ?>>
		<header class="ts-page__header">
			<div class="ts-container">
				<?php ts_child_breadcrumbs(); ?>
				<h1 class="ts-page__title"><?php the_title(); ?></h1>
			</div>
		</header>

		<div class="ts-container">
			<div class="ts-prose">
				<?php the_content(); ?>
				<?php echo ts_child_contact_cards_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Every value is escaped inside ts_child_contact_cards_html(). ?>
			</div>
		</div>
	</main>
	<?php
endwhile;

get_footer();
