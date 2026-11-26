<?php
/**
 * A blog post in a list.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-item' ); ?>>
	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<p class="post-meta"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
	<div class="entry-summary"><?php the_excerpt(); ?></div>
	<a class="link-arrow" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read more', 'tassili-immobilier' ); ?><?php tassili_the_icon( 'arrow', 'flip' ); ?></a>
</article>
