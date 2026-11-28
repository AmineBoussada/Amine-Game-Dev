<?php
/**
 * Template Name: Déco Store — اتصل بنا
 * Template Post Type: page
 *
 * Contact page: the page content (editable in the block editor or Elementor
 * Free) followed by contact cards built from Appearance > Customize >
 * Déco Store > Contact data.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="content" <?php post_class( 'ds-main ds-page ds-page--contact' ); ?>>
		<header class="ds-page__header">
			<div class="ds-container">
				<?php ds_child_breadcrumbs(); ?>
				<h1 class="ds-page__title"><?php the_title(); ?></h1>
			</div>
		</header>

		<div class="ds-container">
			<div class="ds-prose">
				<?php the_content(); ?>
				<?php echo ds_child_contact_cards_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Every value is escaped inside ds_child_contact_cards_html(). ?>
			</div>
		</div>
	</main>
	<?php
endwhile;

get_footer();
