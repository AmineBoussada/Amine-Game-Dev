<?php
/**
 * Template Name: Top Solution — الأقسام
 * Template Post Type: page
 *
 * Categories index: every top-level category as a tile with its subcategories
 * as chips. Text above the grid comes from the page content.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ts_terms = ts_child_has_woocommerce() ? ts_child_get_top_categories( 0, true ) : array();
?>
<main id="content" class="ts-main ts-page ts-page--categories">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<header class="ts-page__header">
			<div class="ts-container">
				<?php ts_child_breadcrumbs(); ?>
				<h1 class="ts-page__title"><?php the_title(); ?></h1>
				<?php if ( '' !== trim( get_the_content() ) ) : ?>
					<div class="ts-page__lead"><?php the_content(); ?></div>
				<?php endif; ?>
			</div>
		</header>
	<?php endwhile; ?>

	<section class="ts-section ts-section--tight">
		<div class="ts-container">
			<?php if ( empty( $ts_terms ) ) : ?>
				<p class="ts-empty"><?php esc_html_e( 'لا توجد أقسام بعد.', 'top-solution-child' ); ?></p>
			<?php else : ?>
				<ul class="ts-cat-groups">
					<?php foreach ( $ts_terms as $ts_term ) : ?>
						<?php
						$ts_children = get_terms(
							array(
								'taxonomy'   => 'product_cat',
								'parent'     => $ts_term->term_id,
								'hide_empty' => true,
								'orderby'    => 'menu_order',
							)
						);
						set_query_var( 'ts_term', $ts_term );
						?>
						<li class="ts-cat-group">
							<ul class="ts-tiles ts-tiles--single"><?php get_template_part( 'template-parts/category-tile' ); ?></ul>
							<?php if ( ! is_wp_error( $ts_children ) && ! empty( $ts_children ) ) : ?>
								<ul class="ts-chips" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: category name. */ __( 'أقسام فرعية: %s', 'top-solution-child' ), $ts_term->name ) ); ?>">
									<?php foreach ( $ts_children as $ts_child ) : ?>
										<li><a class="ts-chip" href="<?php echo esc_url( get_term_link( $ts_child ) ); ?>"><?php echo esc_html( $ts_child->name ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
