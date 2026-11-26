<?php
/**
 * Generic archive (categories, tags, authors, dates).
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
			<h1><?php the_archive_title(); ?></h1>
			<?php the_archive_description( '<p class="muted">', '</p>' ); ?>
		</div>
	</div>
	<div class="container page-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'post' );
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
