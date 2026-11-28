<?php
/**
 * Single page / post layout (overrides the parent template part used by
 * index.php). Used for About, Contact, policies, blog posts, and the
 * WooCommerce cart / checkout / account pages.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

while ( have_posts() ) :
	the_post();
	$ds_is_post = 'post' === get_post_type();
	?>
	<main id="content" <?php post_class( 'ds-main ds-page' ); ?>>
		<?php if ( apply_filters( 'hello_elementor_page_title', true ) ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Parent theme hook. ?>
			<header class="ds-page__header">
				<div class="ds-container">
					<?php ds_child_breadcrumbs(); ?>
					<h1 class="ds-page__title"><?php the_title(); ?></h1>
					<?php if ( $ds_is_post ) : ?>
						<p class="ds-page__meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</p>
					<?php endif; ?>
				</div>
			</header>
		<?php endif; ?>

		<div class="ds-container">
			<div class="ds-prose">
				<?php
				if ( $ds_is_post && has_post_thumbnail() ) {
					the_post_thumbnail( 'large', array( 'class' => 'ds-prose__cover' ) );
				}
				the_content();
				wp_link_pages();
				?>
			</div>
			<?php comments_template(); ?>
		</div>
	</main>
	<?php
endwhile;
