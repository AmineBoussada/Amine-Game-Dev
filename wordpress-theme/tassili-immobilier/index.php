<?php
/**
 * Main fallback template: the blog index and anything no other template handles.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main" class="site-main">
	<div class="page-head">
		<div class="container">
			<h1><?php echo is_home() && ! is_front_page() ? esc_html( single_post_title( '', false ) ) : esc_html__( 'News', 'tassili-immobilier' ); ?></h1>
		</div>
	</div>
	<div class="container page-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', get_post_type() === 'property' ? 'none' : 'post' );
				endwhile;
				?>
			</div>
			<?php tassili_the_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
