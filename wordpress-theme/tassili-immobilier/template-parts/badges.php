<?php
/**
 * Badges of a listing: sale or rent, new, exclusive, furnished.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tassili_p = isset( $args['property'] ) ? $args['property'] : null;
if ( ! $tassili_p ) {
	return;
}
?>
<?php if ( 'available' !== $tassili_p['status'] ) : ?>
	<span class="badge badge-<?php echo esc_attr( $tassili_p['status'] ); ?>"><?php echo esc_html( tassili_status_label( $tassili_p['status'], $tassili_p['tx'] ) ); ?></span>
<?php endif; ?>
<span class="badge <?php echo 'vente' === $tassili_p['tx'] ? 'badge-sale' : 'badge-rent'; ?>">
	<?php echo 'vente' === $tassili_p['tx'] ? esc_html__( 'For sale', 'tassili-immobilier' ) : esc_html__( 'For rent', 'tassili-immobilier' ); ?>
</span>
<?php if ( $tassili_p['is_new'] ) : ?>
	<span class="badge badge-new"><?php esc_html_e( 'New', 'tassili-immobilier' ); ?></span>
<?php endif; ?>
<?php if ( $tassili_p['exclusive'] ) : ?>
	<span class="badge"><?php esc_html_e( 'Exclusive', 'tassili-immobilier' ); ?></span>
<?php endif; ?>
<?php if ( tassili_property_has_feature( $tassili_p['id'], 'meuble' ) ) : ?>
	<span class="badge"><?php esc_html_e( 'Furnished', 'tassili-immobilier' ); ?></span>
<?php endif; ?>
