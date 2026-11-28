<?php
/**
 * Template Name: Déco Store — الأقسام
 * Template Post Type: page
 *
 * Categories index: every top-level category as a tile with its subcategories
 * as chips. Text above the grid comes from the page content.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ds_terms = ds_child_has_woocommerce() ? ds_child_get_top_categories( 0, true ) : array();
?>
<main id="content" class="ds-main ds-page ds-page--categories">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<header class="ds-page__header">
			<div class="ds-container">
				<?php ds_child_breadcrumbs(); ?>
				<h1 class="ds-page__title"><?php the_title(); ?></h1>
				<?php if ( '' !== trim( get_the_content() ) ) : ?>
					<div class="ds-page__lead"><?php the_content(); ?></div>
				<?php endif; ?>
			</div>
		</header>
	<?php endwhile; ?>

	<section class="ds-section ds-section--tight">
		<div class="ds-container">
			<?php if ( empty( $ds_terms ) ) : ?>
				<p class="ds-empty"><?php esc_html_e( 'لا توجد أقسام بعد.', 'deco-store-child' ); ?></p>
			<?php else : ?>
				<ul class="ds-cat-groups">
					<?php foreach ( $ds_terms as $ds_term ) : ?>
						<?php
						$ds_children = get_terms(
							array(
								'taxonomy'   => 'product_cat',
								'parent'     => $ds_term->term_id,
								'hide_empty' => true,
								'orderby'    => 'menu_order',
							)
						);
						set_query_var( 'ds_term', $ds_term );
						?>
						<li class="ds-cat-group">
							<ul class="ds-tiles ds-tiles--single"><?php get_template_part( 'template-parts/category-tile' ); ?></ul>
							<?php if ( ! is_wp_error( $ds_children ) && ! empty( $ds_children ) ) : ?>
								<ul class="ds-chips" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: category name. */ __( 'أقسام فرعية: %s', 'deco-store-child' ), $ds_term->name ) ); ?>">
									<?php foreach ( $ds_children as $ds_child ) : ?>
										<li><a class="ds-chip" href="<?php echo esc_url( get_term_link( $ds_child ) ); ?>"><?php echo esc_html( $ds_child->name ); ?></a></li>
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
