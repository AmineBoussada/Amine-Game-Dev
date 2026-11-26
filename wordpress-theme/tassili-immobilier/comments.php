<?php
/**
 * Comments.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area" aria-labelledby="comments-title">
	<?php if ( have_comments() ) : ?>
		<h2 id="comments-title">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: number of comments. */
					_n( '%s comment', '%s comments', (int) get_comments_number(), 'tassili-immobilier' ),
					number_format_i18n( (int) get_comments_number() )
				)
			);
			?>
		</h2>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
		<?php if ( ! comments_open() ) : ?>
			<p class="muted"><?php esc_html_e( 'Comments are closed.', 'tassili-immobilier' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
