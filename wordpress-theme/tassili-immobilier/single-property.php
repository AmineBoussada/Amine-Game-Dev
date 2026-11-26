<?php
/**
 * Single listing: gallery with lightbox, facts, description, documents, features, location,
 * agent panel with WhatsApp / call / visit request, similar listings and a mobile action bar.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$tassili_id = get_the_ID();
	$tassili_p  = tassili_get_property( $tassili_id );
	if ( ! $tassili_p ) {
		continue;
	}
	$tassili_photos = tassili_property_photos( $tassili_id );
	$tassili_agent  = tassili_get_agent( $tassili_p['agent'] );
	$tassili_wa     = tassili_whatsapp_url( tassili_property_whatsapp_message( $tassili_id ), $tassili_agent ? $tassili_agent['whatsapp'] : '' );
	$tassili_tel    = $tassili_agent ? $tassili_agent['tel'] : tassili_tel_href( (string) tassili_mod( 'phone' ) );
	$tassili_papers = tassili_paper_options();
	$tassili_feats  = tassili_property_features( $tassili_id );
	$tassili_desc   = tassili_property_description( $tassili_id );
	$tassili_min    = ( new DateTimeImmutable( 'today', wp_timezone() ) )->modify( '+1 day' )->format( 'Y-m-d' );

	$tassili_gallery_data = array_map(
		static function ( $photo ) {
			return array(
				'src'     => $photo['large'],
				'full'    => $photo['full'],
				'alt'     => $photo['alt'],
				'caption' => $photo['caption'],
			);
		},
		$tassili_photos
	);

	$tassili_trail = array(
		array(
			'label' => __( 'Home', 'tassili-immobilier' ),
			'url'   => home_url( '/' ),
		),
		array(
			'label' => 'vente' === $tassili_p['tx'] ? __( 'Properties for sale', 'tassili-immobilier' ) : __( 'Properties for rent', 'tassili-immobilier' ),
			'url'   => tassili_archive_url( array( 'tx' => $tassili_p['tx'] ) ),
		),
	);
	if ( $tassili_p['wilaya'] ) {
		$tassili_trail[] = array(
			'label' => tassili_term_name( $tassili_p['wilaya'] ),
			'url'   => get_term_link( $tassili_p['wilaya'] ),
		);
	}
	$tassili_trail[] = array( 'label' => $tassili_p['ref'] );

	$tassili_map = add_query_arg(
		array(
			'api'   => '1',
			'query' => rawurlencode( trim( ( $tassili_p['commune'] ? $tassili_p['commune']->name : '' ) . ', ' . ( $tassili_p['wilaya'] ? $tassili_p['wilaya']->name : '' ) . ', Algérie', ', ' ) ),
		),
		'https://www.google.com/maps/search/'
	);
	?>
<main id="main" class="site-main">
	<div id="property" data-id="<?php echo esc_attr( (string) $tassili_id ); ?>">

		<div class="container property-top">
			<?php tassili_the_breadcrumb( $tassili_trail ); ?>

			<section class="gallery" id="gallery" aria-label="<?php esc_attr_e( 'Property photos', 'tassili-immobilier' ); ?>" data-photos="<?php echo esc_attr( (string) wp_json_encode( $tassili_gallery_data ) ); ?>">
				<div class="g-main" id="g-main">
					<a class="g-open" id="g-open" href="<?php echo esc_url( $tassili_photos[0]['full'] ); ?>" aria-label="<?php esc_attr_e( 'Enlarge the photo', 'tassili-immobilier' ); ?>">
						<img id="g-img" src="<?php echo esc_url( $tassili_photos[0]['large'] ); ?>" alt="<?php echo esc_attr( $tassili_photos[0]['alt'] ); ?>" width="1400" height="933" fetchpriority="high" decoding="async" draggable="false" />
					</a>
					<div class="g-badges"><?php get_template_part( 'template-parts/badges', null, array( 'property' => $tassili_p ) ); ?></div>
					<?php if ( count( $tassili_photos ) > 1 ) : ?>
						<button type="button" class="g-nav g-prev" data-g="-1" aria-label="<?php esc_attr_e( 'Previous photo', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'chev', 'rev' ); ?></button>
						<button type="button" class="g-nav g-next" data-g="1" aria-label="<?php esc_attr_e( 'Next photo', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'chev', 'flip' ); ?></button>
					<?php endif; ?>
					<span class="g-caption" id="g-caption"><?php echo esc_html( $tassili_photos[0]['caption'] ); ?></span>
					<span class="g-counter ltr" id="g-counter" aria-live="polite"><?php echo esc_html( '1 / ' . count( $tassili_photos ) ); ?></span>
				</div>
				<?php if ( count( $tassili_photos ) > 1 ) : ?>
					<div class="g-thumbs">
						<?php foreach ( $tassili_photos as $tassili_i => $tassili_photo ) : ?>
							<button type="button" class="g-thumb" data-thumb="<?php echo esc_attr( (string) $tassili_i ); ?>" aria-current="<?php echo 0 === $tassili_i ? 'true' : 'false'; ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: 1: photo number, 2: caption. */ __( 'Photo %1$d: %2$s', 'tassili-immobilier' ), $tassili_i + 1, $tassili_photo['caption'] ) ); ?>">
								<img src="<?php echo esc_url( $tassili_photo['url'] ); ?>" alt="" width="400" height="300" loading="lazy" decoding="async" draggable="false" />
								<?php if ( 3 === $tassili_i && count( $tassili_photos ) > 4 ) : ?>
									<span class="more"><?php echo esc_html( '+' . ( count( $tassili_photos ) - 4 ) ); ?></span>
								<?php endif; ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</div>

		<div class="container p-layout">
			<div class="p-main">
				<header class="p-head">
					<?php if ( 'available' !== $tassili_p['status'] ) : ?>
						<p class="status-banner status-banner--<?php echo esc_attr( $tassili_p['status'] ); ?>"><?php echo esc_html( tassili_status_label( $tassili_p['status'], $tassili_p['tx'] ) ); ?></p>
					<?php endif; ?>
					<p class="p-ref"><?php esc_html_e( 'Ref.', 'tassili-immobilier' ); ?> <span class="ltr"><?php echo esc_html( $tassili_p['ref'] ); ?></span></p>
					<h1><?php the_title(); ?></h1>
					<p class="p-loc"><?php tassili_the_icon( 'pin' ); ?><?php echo esc_html( tassili_place( $tassili_id ) ); ?></p>
					<p class="p-price"><?php tassili_the_price( $tassili_p['price'], $tassili_p['tx'] ); ?></p>
				</header>

				<h2 class="screen-reader-text"><?php esc_html_e( 'Key facts', 'tassili-immobilier' ); ?></h2>
				<ul class="p-facts">
					<?php if ( $tassili_p['surface'] ) : ?>
						<li><?php tassili_the_icon( 'area' ); ?><div><span><?php esc_html_e( 'Surface', 'tassili-immobilier' ); ?></span><b class="ltr"><?php echo esc_html( tassili_number( $tassili_p['surface'] ) ); ?> m²</b></div></li>
					<?php endif; ?>
					<?php if ( $tassili_p['land'] ) : ?>
						<li><?php tassili_the_icon( 'land' ); ?><div><span><?php esc_html_e( 'Land', 'tassili-immobilier' ); ?></span><b class="ltr"><?php echo esc_html( tassili_number( $tassili_p['land'] ) ); ?> m²</b></div></li>
					<?php endif; ?>
					<?php if ( '' !== $tassili_p['typo'] ) : ?>
						<li><?php tassili_the_icon( 'rooms' ); ?><div><span><?php esc_html_e( 'Typology', 'tassili-immobilier' ); ?></span><b><?php echo esc_html( $tassili_p['typo'] ); ?></b></div></li>
					<?php endif; ?>
					<?php if ( $tassili_p['beds'] ) : ?>
						<li><?php tassili_the_icon( 'bed' ); ?><div><span><?php esc_html_e( 'Bedrooms', 'tassili-immobilier' ); ?></span><b><?php echo esc_html( (string) $tassili_p['beds'] ); ?></b></div></li>
					<?php endif; ?>
					<?php if ( $tassili_p['baths'] ) : ?>
						<li><?php tassili_the_icon( 'bath' ); ?><div><span><?php esc_html_e( 'Bathrooms', 'tassili-immobilier' ); ?></span><b><?php echo esc_html( (string) $tassili_p['baths'] ); ?></b></div></li>
					<?php endif; ?>
					<?php if ( null !== $tassili_p['floor'] ) : ?>
						<li><?php tassili_the_icon( 'layers' ); ?><div><span><?php esc_html_e( 'Floor', 'tassili-immobilier' ); ?></span><b><?php echo esc_html( tassili_floor_label( $tassili_p['floor'] ) ); ?></b></div></li>
					<?php endif; ?>
					<?php if ( $tassili_p['floors'] ) : ?>
						<li><?php tassili_the_icon( 'layers' ); ?><div><span><?php esc_html_e( 'Levels', 'tassili-immobilier' ); ?></span><b><?php echo esc_html( (string) $tassili_p['floors'] ); ?></b></div></li>
					<?php endif; ?>
					<?php if ( $tassili_p['is_new'] && '' !== $tassili_p['delivery'] ) : ?>
						<li><?php tassili_the_icon( 'calendar' ); ?><div><span><?php esc_html_e( 'Delivery', 'tassili-immobilier' ); ?></span><b><?php echo esc_html( $tassili_p['delivery'] ); ?></b></div></li>
					<?php elseif ( $tassili_p['year'] ) : ?>
						<li><?php tassili_the_icon( 'calendar' ); ?><div><span><?php esc_html_e( 'Year built', 'tassili-immobilier' ); ?></span><b class="ltr"><?php echo esc_html( (string) $tassili_p['year'] ); ?></b></div></li>
					<?php endif; ?>
				</ul>

				<?php if ( '' !== $tassili_desc ) : ?>
					<section class="p-sec">
						<h2><?php esc_html_e( 'Description', 'tassili-immobilier' ); ?></h2>
						<div class="p-desc" id="p-desc"><?php echo wp_kses_post( wpautop( esc_html( $tassili_desc ) ) ); ?></div>
						<button type="button" class="read-more" id="read-more" aria-controls="p-desc" aria-expanded="false" hidden><?php esc_html_e( 'Read more', 'tassili-immobilier' ); ?></button>
					</section>
				<?php endif; ?>

				<section class="p-sec">
					<h2><?php esc_html_e( 'Documents and legal status', 'tassili-immobilier' ); ?></h2>
					<ul class="papers">
						<?php foreach ( $tassili_papers as $tassili_pid => $tassili_plabel ) : ?>
							<?php $tassili_has = in_array( $tassili_pid, $tassili_p['papers'], true ); ?>
							<li class="<?php echo $tassili_has ? 'yes' : 'no'; ?>">
								<?php tassili_the_icon( $tassili_has ? 'check' : 'minus' ); ?>
								<span><?php echo esc_html( $tassili_plabel ); ?></span>
								<small><?php echo $tassili_has ? esc_html__( 'Available', 'tassili-immobilier' ) : esc_html__( 'Not provided', 'tassili-immobilier' ); ?></small>
							</li>
						<?php endforeach; ?>
					</ul>
					<p class="f-hint"><?php esc_html_e( 'Documents seen by our legal team before publication.', 'tassili-immobilier' ); ?></p>
				</section>

				<?php if ( $tassili_feats ) : ?>
					<section class="p-sec">
						<h2><?php esc_html_e( 'Features', 'tassili-immobilier' ); ?></h2>
						<ul class="amen-grid">
							<?php foreach ( $tassili_feats as $tassili_feat ) : ?>
								<li><?php tassili_the_icon( 'check' ); ?><?php echo esc_html( $tassili_feat ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<section class="p-sec">
					<h2><?php esc_html_e( 'Location', 'tassili-immobilier' ); ?></h2>
					<div class="loc-card">
						<img src="<?php echo esc_url( tassili_placeholder_url( 'ext-residence-1' ) ); ?>" alt="" width="800" height="600" loading="lazy" />
						<div>
							<p>
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: commune, 2: wilaya. */
										__( '%1$s, wilaya of %2$s. The exact address is given during the visit.', 'tassili-immobilier' ),
										$tassili_p['commune'] ? tassili_term_name( $tassili_p['commune'] ) : '',
										$tassili_p['wilaya'] ? tassili_term_name( $tassili_p['wilaya'] ) : ''
									)
								);
								?>
							</p>
							<a class="link-arrow" id="map-link" href="<?php echo esc_url( $tassili_map ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See the area on Google Maps', 'tassili-immobilier' ); ?><?php tassili_the_icon( 'external', 'ic-sm' ); ?></a>
						</div>
					</div>
				</section>
			</div>

			<aside class="p-aside" aria-label="<?php esc_attr_e( 'Your adviser', 'tassili-immobilier' ); ?>">
				<div class="agent-card">
					<p class="a-price"><?php tassili_the_price( $tassili_p['price'], $tassili_p['tx'], 'small' ); ?></p>
					<div class="agent-actions">
						<a class="btn btn-wa btn-lg" id="agent-wa" href="<?php echo esc_url( $tassili_wa ); ?>" target="_blank" rel="noopener"><?php tassili_the_icon( 'wa' ); ?><?php esc_html_e( 'Write on WhatsApp', 'tassili-immobilier' ); ?></a>
						<div class="row">
							<a class="btn btn-ghost" href="<?php echo esc_url( 'tel:' . $tassili_tel ); ?>"><?php tassili_the_icon( 'phone' ); ?><?php esc_html_e( 'Call', 'tassili-immobilier' ); ?></a>
							<button type="button" class="btn btn-ghost" data-share data-share-title="<?php echo esc_attr( get_the_title() ); ?>"><?php tassili_the_icon( 'share' ); ?><?php esc_html_e( 'Share', 'tassili-immobilier' ); ?></button>
						</div>
						<button type="button" class="btn btn-primary" data-visit><?php tassili_the_icon( 'calendar' ); ?><?php esc_html_e( 'Book a visit', 'tassili-immobilier' ); ?></button>
					</div>
					<?php if ( $tassili_agent ) : ?>
						<div class="agent-who">
							<?php if ( $tassili_agent['photo'] ) : ?>
								<?php
								echo wp_get_attachment_image(
									$tassili_agent['photo'],
									'tassili-avatar',
									false,
									array(
										'class' => 'avatar',
										'alt'   => '',
									)
								);
								?>
							<?php else : ?>
								<span class="avatar"><?php echo esc_html( tassili_initials( $tassili_agent['name'] ) ); ?></span>
							<?php endif; ?>
							<div>
								<span><?php esc_html_e( 'Your adviser', 'tassili-immobilier' ); ?></span>
								<b><?php echo esc_html( $tassili_agent['name'] ); ?></b>
								<span><?php esc_html_e( 'Usually replies within 15 minutes', 'tassili-immobilier' ); ?></span>
							</div>
							<button type="button" class="icon-btn" data-fav="<?php echo esc_attr( (string) $tassili_id ); ?>" aria-pressed="false" aria-label="<?php esc_attr_e( 'Add to favourites', 'tassili-immobilier' ); ?>" style="margin-inline-start:auto"><?php tassili_the_icon( 'heart' ); ?></button>
						</div>
					<?php endif; ?>
				</div>
			</aside>
		</div>

		<?php $tassili_similar = tassili_similar_properties( $tassili_id, 3 ); ?>
		<?php if ( $tassili_similar ) : ?>
			<section class="section section-alt" aria-labelledby="t-similar">
				<div class="container">
					<div class="section-head">
						<h2 id="t-similar"><?php esc_html_e( 'Similar properties', 'tassili-immobilier' ); ?></h2>
						<a class="link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'property' ) ); ?>"><?php esc_html_e( 'See all properties', 'tassili-immobilier' ); ?><?php tassili_the_icon( 'arrow', 'flip' ); ?></a>
					</div>
					<div class="card-grid">
						<?php
						$tassili_sim_query = new WP_Query(
							array(
								'post_type'      => 'property',
								'post__in'       => $tassili_similar,
								'orderby'        => 'post__in',
								'posts_per_page' => 3,
								'no_found_rows'  => true,
							)
						);
						while ( $tassili_sim_query->have_posts() ) :
							$tassili_sim_query->the_post();
							get_template_part( 'template-parts/card', 'property' );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<div class="m-bar" id="m-bar">
			<a class="btn btn-wa" href="<?php echo esc_url( $tassili_wa ); ?>" target="_blank" rel="noopener"><?php tassili_the_icon( 'wa' ); ?><?php esc_html_e( 'WhatsApp', 'tassili-immobilier' ); ?></a>
			<a class="btn btn-ghost" href="<?php echo esc_url( 'tel:' . $tassili_tel ); ?>"><?php tassili_the_icon( 'phone' ); ?><?php esc_html_e( 'Call', 'tassili-immobilier' ); ?></a>
			<button type="button" class="btn btn-primary" data-visit><?php tassili_the_icon( 'calendar' ); ?><span><?php esc_html_e( 'Visit', 'tassili-immobilier' ); ?></span></button>
		</div>

		<dialog class="lightbox" id="lightbox" aria-label="<?php esc_attr_e( 'Property photos', 'tassili-immobilier' ); ?>">
			<div class="lb-in">
				<div class="lb-top">
					<span id="lb-caption-top"></span>
					<span class="ltr" id="lb-counter"></span>
					<button type="button" class="icon-btn lb-close" data-lb-close aria-label="<?php esc_attr_e( 'Close', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'close' ); ?></button>
				</div>
				<div class="lb-stage" id="lb-stage"><img id="lb-img" alt="" draggable="false" /></div>
				<p class="lb-caption" id="lb-caption"></p>
				<button type="button" class="lb-nav lb-prev" data-g="-1" aria-label="<?php esc_attr_e( 'Previous photo', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'chev', 'rev ic-lg' ); ?></button>
				<button type="button" class="lb-nav lb-next" data-g="1" aria-label="<?php esc_attr_e( 'Next photo', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'chev', 'flip ic-lg' ); ?></button>
			</div>
		</dialog>

		<dialog class="modal" id="visit-dialog" aria-labelledby="visit-title">
			<div class="modal-head">
				<h2 id="visit-title"><?php esc_html_e( 'Book a visit', 'tassili-immobilier' ); ?></h2>
				<button type="button" class="icon-btn" data-visit-close aria-label="<?php esc_attr_e( 'Close', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'close' ); ?></button>
			</div>
			<div class="modal-body" id="visit-body">
				<p class="muted"><?php esc_html_e( 'Choose a day and a time slot. Your adviser will call you to confirm.', 'tassili-immobilier' ); ?></p>
				<p class="badge badge-doc" style="justify-self:start"><span class="ltr"><?php echo esc_html( $tassili_p['ref'] ); ?></span>&nbsp;·&nbsp;<?php the_title(); ?></p>
				<form id="visit-form" class="form-grid" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-ajax-action="tassili_visit">
					<?php tassili_form_fields( 'visit' ); ?>
					<input type="hidden" name="property_id" value="<?php echo esc_attr( (string) $tassili_id ); ?>" />
					<div class="form-alert full" data-form-summary hidden role="alert"><?php esc_html_e( 'Check the fields marked in red.', 'tassili-immobilier' ); ?></div>
					<div class="field full">
						<label for="v-name"><?php esc_html_e( 'Full name', 'tassili-immobilier' ); ?></label>
						<input class="control" id="v-name" name="name" autocomplete="name" required data-rule="name" />
						<p class="f-error" id="v-name-err" hidden></p>
					</div>
					<div class="field full">
						<label for="v-phone"><?php esc_html_e( 'Phone', 'tassili-immobilier' ); ?></label>
						<input class="control ltr" id="v-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0555 12 34 56" required data-rule="phone" />
						<p class="f-error" id="v-phone-err" hidden></p>
					</div>
					<div class="field">
						<label for="v-date"><?php esc_html_e( 'Preferred date', 'tassili-immobilier' ); ?></label>
						<input class="control" id="v-date" name="date" type="date" min="<?php echo esc_attr( $tassili_min ); ?>" required data-rule="visitdate" />
						<p class="f-error" id="v-date-err" hidden></p>
					</div>
					<div class="field">
						<label for="v-slot"><?php esc_html_e( 'Time slot', 'tassili-immobilier' ); ?></label>
						<select class="control" id="v-slot" name="slot">
							<option value="am"><?php esc_html_e( 'Morning (9am – 12pm)', 'tassili-immobilier' ); ?></option>
							<option value="pm"><?php esc_html_e( 'Afternoon (1pm – 5pm)', 'tassili-immobilier' ); ?></option>
						</select>
					</div>
					<div class="field full">
						<label for="v-msg"><?php esc_html_e( 'Message', 'tassili-immobilier' ); ?></label>
						<textarea class="control" id="v-msg" name="message" rows="3" placeholder="<?php esc_attr_e( 'Describe your project: wilaya, budget, timing…', 'tassili-immobilier' ); ?>"></textarea>
					</div>
					<div class="full">
						<button class="btn btn-primary btn-lg btn-block" type="submit"><?php esc_html_e( 'Send the request', 'tassili-immobilier' ); ?></button>
					</div>
				</form>
			</div>
		</dialog>
	</div>
</main>
	<?php
endwhile;

get_footer();
