<?php
/**
 * Search form.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tassili_uid = wp_unique_id( 'search-form-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $tassili_uid ); ?>"><?php esc_html_e( 'Search for:', 'tassili-immobilier' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $tassili_uid ); ?>" class="control search-field" placeholder="<?php esc_attr_e( 'Reference, district, type…', 'tassili-immobilier' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
	<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Search', 'tassili-immobilier' ); ?></button>
</form>
