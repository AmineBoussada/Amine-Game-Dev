<?php
/**
 * Blog / generic archive layout (overrides the parent template part).
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="content" class="ds-main ds-page ds-page--archive">
	<header class="ds-page__header">
		<div class="ds-container">
			<?php ds_child_breadcrumbs(); ?>
			<?php the_archive_title( '<h1 class="ds-page__title">', '</h1>' ); ?>
			<?php the_archive_description( '<div class="ds-page__lead">', '</div>' ); ?>
		</div>
	</header>

	<div class="ds-container ds-section ds-section--tight">
		<?php if ( have_posts() ) : ?>
			<div class="ds-post-grid">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/post-card' );
				}
				?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
		<?php else : ?>
			<p class="ds-empty"><?php esc_html_e( 'لا توجد مقالات.', 'deco-store-child' ); ?></p>
		<?php endif; ?>
	</div>
</main>
