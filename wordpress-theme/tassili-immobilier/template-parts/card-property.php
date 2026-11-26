<?php
/**
 * Property card. Used by the home page, the archive, similar listings and the REST cards endpoint.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tassili_p = tassili_get_property( get_the_ID() );
if ( ! $tassili_p ) {
	return;
}
$tassili_id     = $tassili_p['id'];
$tassili_photos = tassili_property_photos( $tassili_id );
$tassili_agent  = tassili_get_agent( $tassili_p['agent'] );
$tassili_rooms  = (int) $tassili_p['rooms'];
$tassili_beds   = (int) $tassili_p['beds'];
$tassili_wa     = tassili_whatsapp_url( tassili_property_whatsapp_message( $tassili_id ), $tassili_agent ? $tassili_agent['whatsapp'] : '' );
?>
<article class="card" data-id="<?php echo esc_attr( (string) $tassili_id ); ?>">
	<div class="card-media">
		<?php tassili_the_card_image( $tassili_id ); ?>
		<div class="card-badges"><?php get_template_part( 'template-parts/badges', null, array( 'property' => $tassili_p ) ); ?></div>
		<button type="button" class="fav" data-fav="<?php echo esc_attr( (string) $tassili_id ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Add to favourites', 'tassili-immobilier' ); ?>">
			<?php tassili_the_icon( 'heart' ); ?>
		</button>
		<span class="card-ref ltr"><?php echo esc_html( $tassili_p['ref'] ); ?></span>
		<span class="card-count">
			<?php tassili_the_icon( 'camera' ); ?>
			<?php
			/* translators: %s: number of photos. */
			echo esc_html( sprintf( _n( '%s photo', '%s photos', count( $tassili_photos ), 'tassili-immobilier' ), number_format_i18n( count( $tassili_photos ) ) ) );
			?>
		</span>
	</div>

	<div class="card-body">
		<p class="price"><?php tassili_the_price( $tassili_p['price'], $tassili_p['tx'] ); ?></p>
		<h3 class="card-title"><a class="card-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="card-loc"><?php tassili_the_icon( 'pin' ); ?><?php echo esc_html( tassili_place( $tassili_id ) ); ?></p>
		<ul class="facts">
			<?php if ( $tassili_p['surface'] ) : ?>
				<li><?php tassili_the_icon( 'area' ); ?><b class="ltr"><?php echo esc_html( tassili_number( $tassili_p['surface'] ) ); ?> m²</b></li>
			<?php endif; ?>
			<?php if ( $tassili_rooms ) : ?>
				<li>
					<?php tassili_the_icon( 'rooms' ); ?>
					<?php
					/* translators: %s: number of rooms. */
					echo wp_kses( sprintf( _n( '%s room', '%s rooms', $tassili_rooms, 'tassili-immobilier' ), '<b>' . $tassili_rooms . '</b>' ), array( 'b' => array() ) );
					?>
				</li>
			<?php endif; ?>
			<?php if ( $tassili_beds ) : ?>
				<li>
					<?php tassili_the_icon( 'bed' ); ?>
					<?php
					/* translators: %s: number of bedrooms (short form "bd."). */
					echo wp_kses( sprintf( __( '%s bd.', 'tassili-immobilier' ), '<b>' . $tassili_beds . '</b>' ), array( 'b' => array() ) );
					?>
				</li>
			<?php endif; ?>
			<?php if ( $tassili_p['is_new'] && '' !== $tassili_p['delivery'] ) : ?>
				<li><?php tassili_the_icon( 'calendar' ); ?><?php echo esc_html( $tassili_p['delivery'] ); ?></li>
			<?php elseif ( in_array( 'acte', $tassili_p['papers'], true ) ) : ?>
				<li class="ok"><?php tassili_the_icon( 'check' ); ?><?php esc_html_e( 'Notarial deed', 'tassili-immobilier' ); ?></li>
			<?php endif; ?>
		</ul>
	</div>

	<div class="card-foot">
		<a class="btn btn-ghost btn-sm" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php esc_html_e( 'View property', 'tassili-immobilier' ); ?></a>
		<a class="btn btn-wa btn-sm btn-icon" href="<?php echo esc_url( $tassili_wa ); ?>" target="_blank" rel="noopener"
			aria-label="<?php echo esc_attr( sprintf( /* translators: %s: listing reference. */ __( 'WhatsApp about property %s', 'tassili-immobilier' ), $tassili_p['ref'] ) ); ?>">
			<?php tassili_the_icon( 'wa' ); ?>
		</a>
	</div>
</article>
