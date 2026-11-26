<?php
/**
 * Nothing found.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="empty">
	<?php tassili_the_icon( 'search' ); ?>
	<h2><?php esc_html_e( 'Nothing found', 'tassili-immobilier' ); ?></h2>
	<p><?php esc_html_e( 'Try different words, or browse our properties.', 'tassili-immobilier' ); ?></p>
	<?php get_search_form(); ?>
</div>
