<?php
/**
 * Blog / generic archive layout (overrides the parent template part).
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;
?>
<main id="content" class="ts-main ts-page ts-page--archive">
	<header class="ts-page__header">
		<div class="ts-container">
			<?php ts_child_breadcrumbs(); ?>
			<?php the_archive_title( '<h1 class="ts-page__title">', '</h1>' ); ?>
			<?php the_archive_description( '<div class="ts-page__lead">', '</div>' ); ?>
		</div>
	</header>

	<div class="ts-container ts-section ts-section--tight">
		<?php if ( have_posts() ) : ?>
			<div class="ts-post-grid">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/post-card' );
				}
				?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
		<?php else : ?>
			<p class="ts-empty"><?php esc_html_e( 'لا توجد مقالات.', 'top-solution-child' ); ?></p>
		<?php endif; ?>
	</div>
</main>
