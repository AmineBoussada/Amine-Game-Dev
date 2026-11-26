<?php
/**
 * Single blog post.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="page-head">
			<div class="container">
				<h1><?php the_title(); ?></h1>
				<p class="post-meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<?php if ( has_category() ) : ?>
						&middot; <?php the_category( ', ' ); ?>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<div class="container page-wrap">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="entry-thumb" style="max-width:72ch;margin-bottom:var(--s-5)"><?php the_post_thumbnail( 'large', array( 'style' => 'border-radius:var(--r-lg)' ) ); ?></div>
				<?php endif; ?>
				<div class="entry-content">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'tassili-immobilier' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>
				<?php the_tags( '<p class="post-meta">' . esc_html__( 'Tags:', 'tassili-immobilier' ) . ' ', ', ', '</p>' ); ?>
			</article>
			<?php
			the_post_navigation(
				array(
					'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous post:', 'tassili-immobilier' ) . '</span> %title',
					'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next post:', 'tassili-immobilier' ) . '</span> %title',
				)
			);
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();
