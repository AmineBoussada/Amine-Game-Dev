<?php
/**
 * Trust bar: up to three short items from the Customizer.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_items = array(
	array( 'wallet', ds_child_get( 'trust_1' ) ),
	array( 'truck', ds_child_get( 'trust_2' ) ),
	array( 'store', ds_child_get( 'trust_3' ) ),
);
$ds_items = array_filter(
	$ds_items,
	static function ( $item ) {
		return '' !== $item[1];
	}
);
if ( empty( $ds_items ) ) {
	return;
}
?>
<ul class="ds-trust">
	<?php foreach ( $ds_items as $ds_item ) : ?>
		<li class="ds-trust__item">
			<span class="ds-trust__icon"><?php ds_child_the_icon( $ds_item[0] ); ?></span>
			<span class="ds-trust__text"><?php echo esc_html( $ds_item[1] ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
