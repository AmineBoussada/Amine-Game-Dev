<?php
/**
 * Trust bar: up to three short items from the Customizer.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_items = array(
	array( 'wallet', ts_child_get( 'trust_1' ) ),
	array( 'truck', ts_child_get( 'trust_2' ) ),
	array( 'store', ts_child_get( 'trust_3' ) ),
);
$ts_items = array_filter(
	$ts_items,
	static function ( $item ) {
		return '' !== $item[1];
	}
);
if ( empty( $ts_items ) ) {
	return;
}
?>
<ul class="ts-trust">
	<?php foreach ( $ts_items as $ts_item ) : ?>
		<li class="ts-trust__item">
			<span class="ts-trust__icon"><?php ts_child_the_icon( $ts_item[0] ); ?></span>
			<span class="ts-trust__text"><?php echo esc_html( $ts_item[1] ); ?></span>
		</li>
	<?php endforeach; ?>
</ul>
