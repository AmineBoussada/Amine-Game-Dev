<?php
/**
 * Post card used by archive and search results.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;
?>
<article <?php post_class( 'ts-post-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="ts-post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php endif; ?>
	<div class="ts-post-card__body">
		<h2 class="ts-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p class="ts-post-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
	</div>
</article>
