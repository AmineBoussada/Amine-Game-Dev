<?php
/**
 * Property search panel (home page hero and the [tassili_search] shortcode).
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tassili_initial = tassili_count_listings( tassili_normalize_filters( array( 'tx' => 'vente' ) ) );
$tassili_wilayas = get_terms(
	array(
		'taxonomy'   => 'wilaya',
		'parent'     => 0,
		'hide_empty' => true,
	)
);
$tassili_types   = get_terms(
	array(
		'taxonomy'   => 'property_type',
		'hide_empty' => false,
	)
);
?>
<div class="search-panel" id="hero-search">
	<form id="home-search" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'property' ) ); ?>" role="search" aria-label="<?php esc_attr_e( 'Search for a property', 'tassili-immobilier' ); ?>">
		<div class="tabs" role="radiogroup" aria-label="<?php esc_attr_e( 'Transaction', 'tassili-immobilier' ); ?>">
			<label class="tab"><input class="sr-only" type="radio" name="tx" value="vente" checked /><?php esc_html_e( 'Buy', 'tassili-immobilier' ); ?></label>
			<label class="tab"><input class="sr-only" type="radio" name="tx" value="location" /><?php esc_html_e( 'Rent', 'tassili-immobilier' ); ?></label>
			<label class="tab"><input class="sr-only" type="radio" name="tx" value="neuf" /><?php esc_html_e( 'New', 'tassili-immobilier' ); ?></label>
		</div>
		<div class="s-fields">
			<div class="field">
				<label for="hs-wilaya"><?php esc_html_e( 'Wilaya', 'tassili-immobilier' ); ?></label>
				<select class="control" id="hs-wilaya" name="wilaya">
					<option value=""><?php esc_html_e( 'All wilayas', 'tassili-immobilier' ); ?></option>
					<?php foreach ( (array) $tassili_wilayas as $tassili_w ) : ?>
						<option value="<?php echo esc_attr( $tassili_w->slug ); ?>"><?php echo esc_html( tassili_term_name( $tassili_w ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field s-extra">
				<label for="hs-commune"><?php esc_html_e( 'Commune', 'tassili-immobilier' ); ?></label>
				<select class="control" id="hs-commune" name="commune" disabled>
					<option value=""><?php esc_html_e( 'All communes', 'tassili-immobilier' ); ?></option>
				</select>
			</div>
			<div class="field">
				<label for="hs-type"><?php esc_html_e( 'Property type', 'tassili-immobilier' ); ?></label>
				<select class="control" id="hs-type" name="type">
					<option value=""><?php esc_html_e( 'All types', 'tassili-immobilier' ); ?></option>
					<?php foreach ( (array) $tassili_types as $tassili_t ) : ?>
						<option value="<?php echo esc_attr( $tassili_t->slug ); ?>"><?php echo esc_html( tassili_term_name( $tassili_t ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field s-extra">
				<label for="hs-typo"><?php esc_html_e( 'Typology', 'tassili-immobilier' ); ?></label>
				<select class="control" id="hs-typo" name="typo">
					<option value=""><?php esc_html_e( 'Any', 'tassili-immobilier' ); ?></option>
					<?php foreach ( tassili_typology_chips() as $tassili_typo ) : ?>
						<option value="<?php echo esc_attr( $tassili_typo ); ?>"><?php echo esc_html( $tassili_typo ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field s-extra">
				<label for="hs-budget"><?php esc_html_e( 'Maximum budget (DA)', 'tassili-immobilier' ); ?></label>
				<input class="control ltr" id="hs-budget" name="pmax" type="text" inputmode="numeric" autocomplete="off" placeholder="<?php echo esc_attr( tassili_number( 25000000 ) ); ?>" />
			</div>
			<div class="s-go-wrap">
				<button class="btn btn-primary btn-lg s-go" type="submit" id="hs-go">
					<?php tassili_the_icon( 'search' ); ?>
					<span id="hs-go-label" data-count="<?php echo esc_attr( (string) $tassili_initial ); ?>">
						<?php
						echo esc_html(
							0 === $tassili_initial ? __( 'No properties', 'tassili-immobilier' ) : sprintf(
								/* translators: %s: number of properties. */
								_n( 'View %s property', 'View %s properties', $tassili_initial, 'tassili-immobilier' ),
								number_format_i18n( $tassili_initial )
							)
						);
						?>
					</span>
				</button>
			</div>
		</div>
		<button type="button" class="s-more" id="hs-more" aria-expanded="false"><?php esc_html_e( '+ More criteria', 'tassili-immobilier' ); ?></button>
	</form>
</div>
