<?php
/**
 * Shortcodes : liste d'annonces (cartes + carte) et fiche détaillée.
 * La liste est rendue côté serveur (SEO) puis mise à jour par assets/js/map-listings.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carte d'annonce — structure reprise du design de référence :
 * photo plein cadre, badge type, bouton favori, prix, titre, localisation, date.
 */
function nova_immo_render_card( array $l ) {
	$badge = $l['transaction'] ? ( 'location' === $l['transaction'] ? __( 'Location', 'nova-immo' ) : __( 'Vente', 'nova-immo' ) ) : '';
	ob_start(); ?>
	<article class="nova-card" data-id="<?php echo esc_attr( $l['id'] ); ?>" data-lat="<?php echo esc_attr( $l['lat'] ); ?>" data-lng="<?php echo esc_attr( $l['lng'] ); ?>" tabindex="0">
		<a class="nova-card__link" href="<?php echo esc_url( $l['url'] ); ?>" aria-label="<?php echo esc_attr( $l['title'] ); ?>">
			<?php if ( $l['image'] ) : ?>
				<img class="nova-card__img" src="<?php echo esc_url( $l['image'] ); ?>" alt="<?php echo esc_attr( $l['title'] ); ?>" loading="lazy" width="720" height="960">
			<?php else : ?>
				<span class="nova-card__img nova-card__img--empty" aria-hidden="true"></span>
			<?php endif; ?>
		</a>
		<div class="nova-card__top">
			<span class="nova-chip"><?php echo esc_html( $l['type'] ); ?></span>
			<?php if ( $badge ) : ?><span class="nova-chip nova-chip--tx"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
			<button class="nova-card__fav" type="button" aria-pressed="false" aria-label="<?php esc_attr_e( 'Ajouter aux favoris', 'nova-immo' ); ?>" data-fav="<?php echo esc_attr( $l['id'] ); ?>">♥</button>
		</div>
		<div class="nova-card__body">
			<p class="nova-card__price"><?php echo esc_html( $l['prix_label'] ); ?></p>
			<h3 class="nova-card__title"><a href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['title'] ); ?></a></h3>
			<div class="nova-card__meta">
				<span class="nova-card__loc">📍 <?php echo esc_html( $l['wilaya'] ); ?></span>
				<span class="nova-card__date"><?php echo esc_html( $l['date'] ); ?></span>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

add_shortcode( 'nova_listings', function ( $atts ) {
	$atts = shortcode_atts( array( 'view' => 'archive', 'per_page' => 12 ), $atts, 'nova_listings' );

	$query = new WP_Query( array(
		'post_type'      => 'annonce',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['per_page'],
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	ob_start(); ?>
	<section class="nova-listings" data-view="<?php echo esc_attr( $atts['view'] ); ?>">
		<?php if ( 'archive' === $atts['view'] ) : ?>
		<form class="nova-filters" aria-label="<?php esc_attr_e( 'Filtres', 'nova-immo' ); ?>">
			<select name="transaction" aria-label="<?php esc_attr_e( 'Transaction', 'nova-immo' ); ?>"><option value=""><?php esc_html_e( 'Vente et location', 'nova-immo' ); ?></option><option value="vente"><?php esc_html_e( 'Vente', 'nova-immo' ); ?></option><option value="location"><?php esc_html_e( 'Location', 'nova-immo' ); ?></option></select>
			<select name="type" aria-label="<?php esc_attr_e( 'Type de bien', 'nova-immo' ); ?>"><option value=""><?php esc_html_e( 'Tous types', 'nova-immo' ); ?></option><option value="appartement"><?php esc_html_e( 'Appartement', 'nova-immo' ); ?></option><option value="villa"><?php esc_html_e( 'Villa', 'nova-immo' ); ?></option><option value="terrain"><?php esc_html_e( 'Terrain', 'nova-immo' ); ?></option><option value="local-commercial"><?php esc_html_e( 'Local commercial', 'nova-immo' ); ?></option></select>
			<input name="wilaya" type="search" placeholder="<?php esc_attr_e( 'Wilaya', 'nova-immo' ); ?>" aria-label="<?php esc_attr_e( 'Wilaya', 'nova-immo' ); ?>">
			<input name="budget_min" type="number" min="0" placeholder="<?php esc_attr_e( 'Budget min (DA)', 'nova-immo' ); ?>">
			<input name="budget_max" type="number" min="0" placeholder="<?php esc_attr_e( 'Budget max (DA)', 'nova-immo' ); ?>">
			<select name="sort" aria-label="<?php esc_attr_e( 'Trier', 'nova-immo' ); ?>"><option value="recent"><?php esc_html_e( 'Plus récentes', 'nova-immo' ); ?></option><option value="prix_asc"><?php esc_html_e( 'Prix croissant', 'nova-immo' ); ?></option><option value="prix_desc"><?php esc_html_e( 'Prix décroissant', 'nova-immo' ); ?></option></select>
			<button type="button" class="nova-map-toggle" aria-pressed="false"><?php esc_html_e( 'La carte', 'nova-immo' ); ?> 👁</button>
		</form>
		<?php endif; ?>
		<div class="nova-listings__layout">
			<div class="nova-grid" aria-live="polite">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					echo nova_immo_render_card( nova_immo_get_listing( get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				endwhile;
				wp_reset_postdata();
				?>
			</div>
			<div id="nova-map" class="nova-map" role="region" aria-label="<?php esc_attr_e( 'Carte des annonces', 'nova-immo' ); ?>"></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

add_shortcode( 'nova_annonce_details', function () {
	$l = nova_immo_get_listing( get_the_ID() );
	if ( ! $l ) {
		return '';
	}
	$wa = 'https://wa.me/' . nova_immo_whatsapp_number() . '?text=' . rawurlencode( sprintf( __( 'Bonjour, je suis intéressé par : %s (%s)', 'nova-immo' ), $l['title'], $l['reference'] ) );
	ob_start(); ?>
	<article class="nova-detail">
		<header class="nova-detail__head">
			<p class="nova-chip"><?php echo esc_html( $l['type'] ); ?> · <?php echo esc_html( $l['transaction'] ); ?></p>
			<h1><?php echo esc_html( $l['title'] ); ?></h1>
			<p class="nova-detail__price"><?php echo esc_html( $l['prix_label'] ); ?></p>
			<p class="nova-detail__loc">📍 <?php echo esc_html( $l['adresse'] ? $l['adresse'] . ', ' : '' ); ?><?php echo esc_html( $l['wilaya'] ); ?> · <?php echo esc_html( $l['reference'] ); ?></p>
		</header>
		<?php if ( $l['gallery'] || $l['image'] ) : ?>
		<div class="nova-gallery">
			<img class="nova-gallery__main" src="<?php echo esc_url( $l['gallery'][0] ?? $l['image'] ); ?>" alt="<?php echo esc_attr( $l['title'] ); ?>">
		</div>
		<?php endif; ?>
		<ul class="nova-specs">
			<li><strong><?php echo (int) $l['surface']; ?> m²</strong><span><?php esc_html_e( 'Surface', 'nova-immo' ); ?></span></li>
			<li><strong><?php echo (int) $l['pieces']; ?></strong><span><?php esc_html_e( 'Pièces', 'nova-immo' ); ?></span></li>
			<li><strong><?php echo (int) $l['salles_bain']; ?></strong><span><?php esc_html_e( 'Salles de bain', 'nova-immo' ); ?></span></li>
			<?php if ( $l['etage'] ) : ?><li><strong><?php echo esc_html( $l['etage'] ); ?></strong><span><?php esc_html_e( 'Étage', 'nova-immo' ); ?></span></li><?php endif; ?>
		</ul>
		<div class="nova-detail__cta">
			<a class="nova-btn nova-btn--primary" href="#contact"><?php esc_html_e( 'Demander une visite', 'nova-immo' ); ?></a>
			<a class="nova-btn" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'WhatsApp', 'nova-immo' ); ?></a>
		</div>
		<div class="nova-detail__content"><?php the_content(); ?></div>
	</article>
	<?php
	return ob_get_clean();
} );
