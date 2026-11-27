<?php
/**
 * Single page / post layout (overrides the parent template part used by
 * index.php). Used for About, Contact, policies, blog posts, and the
 * WooCommerce cart / checkout / account pages.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

while ( have_posts() ) :
	the_post();
	$ts_is_post = 'post' === get_post_type();
	?>
	<main id="content" <?php post_class( 'ts-main ts-page' ); ?>>
		<?php if ( apply_filters( 'hello_elementor_page_title', true ) ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Parent theme hook. ?>
			<header class="ts-page__header">
				<div class="ts-container">
					<?php ts_child_breadcrumbs(); ?>
					<h1 class="ts-page__title"><?php the_title(); ?></h1>
					<?php if ( $ts_is_post ) : ?>
						<p class="ts-page__meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</p>
					<?php endif; ?>
				</div>
			</header>
		<?php endif; ?>

		<div class="ts-container">
			<div class="ts-prose">
				<?php
				if ( $ts_is_post && has_post_thumbnail() ) {
					the_post_thumbnail( 'large', array( 'class' => 'ts-prose__cover' ) );
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
