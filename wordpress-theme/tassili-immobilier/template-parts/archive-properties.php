<?php
/**
 * Search results page: filters, toolbar, active filters, results grid, pagination.
 * Shared by archive-property.php and the taxonomy archives.
 *
 * Works without JavaScript (the filter form is a plain GET form). With JavaScript the
 * results are swapped in place, see assets/js/search.js.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;
$tassili_f        = tassili_effective_filters();
$tassili_found    = (int) $wp_query->found_posts;
$tassili_active   = tassili_active_filter_count( $tassili_f );
$tassili_chips    = tassili_active_chips( $tassili_f );
$tassili_action   = get_post_type_archive_link( 'property' );
$tassili_wilayas  = get_terms(
	array(
		'taxonomy'   => 'wilaya',
		'parent'     => 0,
		'hide_empty' => true,
	)
);
$tassili_wil_obj  = $tassili_f['wilaya'] ? get_term_by( 'slug', $tassili_f['wilaya'], 'wilaya' ) : null;
$tassili_communes = $tassili_wil_obj instanceof WP_Term ? get_terms(
	array(
		'taxonomy'   => 'wilaya',
		'parent'     => $tassili_wil_obj->term_id,
		'hide_empty' => false,
	)
) : array();
$tassili_types    = get_terms(
	array(
		'taxonomy'   => 'property_type',
		'hide_empty' => false,
	)
);
$tassili_feats    = get_terms(
	array(
		'taxonomy'   => 'feature',
		'hide_empty' => false,
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a dozen terms.
			array(
				'key'   => 'tassili_filter',
				'value' => '1',
			),
		),
	)
);
$tassili_papers   = array_slice( tassili_paper_options(), 0, 3, true );
$tassili_title    = tassili_archive_title();

$tassili_trail = array(
	array(
		'label' => __( 'Home', 'tassili-immobilier' ),
		'url'   => home_url( '/' ),
	),
	array( 'label' => __( 'Properties', 'tassili-immobilier' ) ),
);
?>
<main id="main" class="site-main">
	<div id="archive" data-archive-url="<?php echo esc_url( $tassili_action ); ?>">
		<div class="page-head">
			<div class="container">
				<?php tassili_the_breadcrumb( $tassili_trail ); ?>
				<h1 id="a-title"><?php echo esc_html( $tassili_title ); ?></h1>
				<p class="result-count" id="a-count" role="status" aria-live="polite"><?php echo esc_html( tassili_count_label( $tassili_found ) ); ?></p>
			</div>
		</div>

		<div class="container archive-layout">
			<aside class="filters" id="filters" aria-labelledby="filters-title">
				<div class="filters-head">
					<h2 id="filters-title"><?php esc_html_e( 'Filters', 'tassili-immobilier' ); ?></h2>
					<a class="filters-reset" href="<?php echo esc_url( $tassili_action ); ?>" data-reset><?php esc_html_e( 'Reset', 'tassili-immobilier' ); ?></a>
					<button type="button" class="icon-btn filters-close" data-sheet-close aria-label="<?php esc_attr_e( 'Close the filters', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'close' ); ?></button>
				</div>

				<form class="filters-body" id="filter-form" method="get" action="<?php echo esc_url( $tassili_action ); ?>">
					<div class="f-group">
						<span class="field-label" id="fl-tx"><?php esc_html_e( 'Transaction', 'tassili-immobilier' ); ?></span>
						<div class="seg" role="radiogroup" aria-labelledby="fl-tx">
							<?php
							$tassili_mode = $tassili_f['neuf'] ? 'neuf' : $tassili_f['tx'];
							$tassili_seg  = array(
								''         => __( 'All', 'tassili-immobilier' ),
								'vente'    => __( 'Buy', 'tassili-immobilier' ),
								'location' => __( 'Rent', 'tassili-immobilier' ),
								'neuf'     => __( 'New', 'tassili-immobilier' ),
							);
							foreach ( $tassili_seg as $tassili_val => $tassili_lab ) :
								?>
								<label><input type="radio" name="tx" value="<?php echo esc_attr( $tassili_val ); ?>"<?php checked( $tassili_mode, $tassili_val ); ?> /><span><?php echo esc_html( $tassili_lab ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="f-group">
						<div class="field">
							<label for="ff-wilaya"><?php esc_html_e( 'Wilaya', 'tassili-immobilier' ); ?></label>
							<select class="control" id="ff-wilaya" name="wilaya">
								<option value=""><?php esc_html_e( 'All wilayas', 'tassili-immobilier' ); ?></option>
								<?php foreach ( (array) $tassili_wilayas as $tassili_w ) : ?>
									<option value="<?php echo esc_attr( $tassili_w->slug ); ?>"<?php selected( $tassili_f['wilaya'], $tassili_w->slug ); ?>><?php echo esc_html( tassili_term_name( $tassili_w ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="field">
							<label for="ff-commune"><?php esc_html_e( 'Commune', 'tassili-immobilier' ); ?></label>
							<select class="control" id="ff-commune" name="commune"<?php disabled( empty( $tassili_communes ) ); ?>>
								<option value=""><?php esc_html_e( 'All communes', 'tassili-immobilier' ); ?></option>
								<?php foreach ( (array) $tassili_communes as $tassili_c ) : ?>
									<option value="<?php echo esc_attr( $tassili_c->slug ); ?>"<?php selected( $tassili_f['commune'], $tassili_c->slug ); ?>><?php echo esc_html( tassili_term_name( $tassili_c ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="field">
							<label for="ff-type"><?php esc_html_e( 'Property type', 'tassili-immobilier' ); ?></label>
							<select class="control" id="ff-type" name="type">
								<option value=""><?php esc_html_e( 'All types', 'tassili-immobilier' ); ?></option>
								<?php foreach ( (array) $tassili_types as $tassili_t ) : ?>
									<option value="<?php echo esc_attr( $tassili_t->slug ); ?>"<?php selected( $tassili_f['type'], $tassili_t->slug ); ?>><?php echo esc_html( tassili_term_name( $tassili_t ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="f-group">
						<span class="field-label"><?php esc_html_e( 'Typology', 'tassili-immobilier' ); ?></span>
						<div class="chips-wrap">
							<?php foreach ( tassili_typology_chips() as $tassili_typo ) : ?>
								<label class="chip-check"><input type="checkbox" name="typo[]" value="<?php echo esc_attr( $tassili_typo ); ?>"<?php checked( in_array( $tassili_typo, $tassili_f['typo'], true ) ); ?> /><span><?php echo esc_html( $tassili_typo ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="f-group">
						<span class="field-label"><?php esc_html_e( 'Price (DA)', 'tassili-immobilier' ); ?></span>
						<div class="range">
							<div class="field">
								<label class="screen-reader-text" for="ff-pmin"><?php esc_html_e( 'Minimum price', 'tassili-immobilier' ); ?></label>
								<input class="control ltr" id="ff-pmin" name="pmin" type="text" inputmode="numeric" autocomplete="off" placeholder="<?php esc_attr_e( 'Min', 'tassili-immobilier' ); ?>" value="<?php echo esc_attr( $tassili_f['pmin'] ? tassili_number( $tassili_f['pmin'] ) : '' ); ?>" />
							</div>
							<div class="field">
								<label class="screen-reader-text" for="ff-pmax"><?php esc_html_e( 'Maximum price', 'tassili-immobilier' ); ?></label>
								<input class="control ltr" id="ff-pmax" name="pmax" type="text" inputmode="numeric" autocomplete="off" placeholder="<?php esc_attr_e( 'Max', 'tassili-immobilier' ); ?>" value="<?php echo esc_attr( $tassili_f['pmax'] ? tassili_number( $tassili_f['pmax'] ) : '' ); ?>" />
							</div>
						</div>
						<div class="field">
							<label for="ff-smin"><?php esc_html_e( 'Minimum surface (m²)', 'tassili-immobilier' ); ?></label>
							<input class="control ltr" id="ff-smin" name="smin" type="text" inputmode="numeric" autocomplete="off" value="<?php echo esc_attr( $tassili_f['smin'] ? (string) $tassili_f['smin'] : '' ); ?>" />
						</div>
					</div>

					<div class="f-group">
						<span class="field-label"><?php esc_html_e( 'Documents', 'tassili-immobilier' ); ?></span>
						<div class="checks">
							<?php foreach ( $tassili_papers as $tassili_pid => $tassili_plabel ) : ?>
								<label class="check"><input type="checkbox" name="papers[]" value="<?php echo esc_attr( $tassili_pid ); ?>"<?php checked( in_array( $tassili_pid, $tassili_f['papers'], true ) ); ?> /><span><?php echo esc_html( $tassili_plabel ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</div>

					<?php if ( ! empty( $tassili_feats ) && ! is_wp_error( $tassili_feats ) ) : ?>
						<div class="f-group">
							<span class="field-label"><?php esc_html_e( 'Features', 'tassili-immobilier' ); ?></span>
							<div class="chips-wrap">
								<?php foreach ( $tassili_feats as $tassili_feat ) : ?>
									<label class="chip-check"><input type="checkbox" name="amen[]" value="<?php echo esc_attr( $tassili_feat->slug ); ?>"<?php checked( in_array( $tassili_feat->slug, $tassili_f['amen'], true ) ); ?> /><span><?php echo esc_html( tassili_term_name( $tassili_feat ) ); ?></span></label>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>

					<div class="f-group no-js-only">
						<button type="submit" class="btn btn-primary btn-block"><?php esc_html_e( 'Search', 'tassili-immobilier' ); ?></button>
					</div>
				</form>

				<div class="filters-foot">
					<a class="filters-reset" href="<?php echo esc_url( $tassili_action ); ?>" data-reset><?php esc_html_e( 'Reset', 'tassili-immobilier' ); ?></a>
					<button type="button" class="btn btn-primary" data-sheet-close id="filters-apply" data-count="<?php echo esc_attr( (string) $tassili_found ); ?>">
						<?php
						echo esc_html(
							0 === $tassili_found ? __( 'No properties', 'tassili-immobilier' ) : sprintf(
								/* translators: %s: number of properties. */
								_n( 'View %s property', 'View %s properties', $tassili_found, 'tassili-immobilier' ),
								number_format_i18n( $tassili_found )
							)
						);
						?>
					</button>
				</div>
			</aside>

			<section class="results-col" aria-labelledby="a-title">
				<div class="toolbar">
					<button type="button" class="btn btn-ghost btn-sm filter-open" id="filter-open" aria-controls="filters" aria-expanded="false">
						<?php tassili_the_icon( 'filter' ); ?>
						<span id="filter-open-label">
							<?php
							echo esc_html(
								$tassili_active ? sprintf(
									/* translators: %d: number of active filters. */
									__( 'Filters (%d)', 'tassili-immobilier' ),
									$tassili_active
								) : __( 'Filters', 'tassili-immobilier' )
							);
							?>
						</span>
					</button>
					<div class="toolbar-end">
						<label class="screen-reader-text" for="sort"><?php esc_html_e( 'Sort by', 'tassili-immobilier' ); ?></label>
						<select class="control" id="sort" name="sort" form="filter-form">
							<option value="recent"<?php selected( $tassili_f['sort'], 'recent' ); ?>><?php esc_html_e( 'Most recent', 'tassili-immobilier' ); ?></option>
							<option value="price_asc"<?php selected( $tassili_f['sort'], 'price_asc' ); ?>><?php esc_html_e( 'Price, low to high', 'tassili-immobilier' ); ?></option>
							<option value="price_desc"<?php selected( $tassili_f['sort'], 'price_desc' ); ?>><?php esc_html_e( 'Price, high to low', 'tassili-immobilier' ); ?></option>
							<option value="surface"<?php selected( $tassili_f['sort'], 'surface' ); ?>><?php esc_html_e( 'Largest surface', 'tassili-immobilier' ); ?></option>
						</select>
						<div class="view-toggle" role="group" aria-label="<?php esc_attr_e( 'Display', 'tassili-immobilier' ); ?>">
							<button type="submit" form="filter-form" name="view" value="grid" data-view="grid" aria-pressed="<?php echo 'grid' === $tassili_f['view'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Grid', 'tassili-immobilier' ); ?>" title="<?php esc_attr_e( 'Grid', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'grid' ); ?></button>
							<button type="submit" form="filter-form" name="view" value="list" data-view="list" aria-pressed="<?php echo 'list' === $tassili_f['view'] ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'List', 'tassili-immobilier' ); ?>" title="<?php esc_attr_e( 'List', 'tassili-immobilier' ); ?>"><?php tassili_the_icon( 'list' ); ?></button>
						</div>
					</div>
				</div>

				<div id="results-wrap">
				<div class="active-chips" id="chips"<?php echo $tassili_chips ? '' : ' hidden'; ?>>
					<?php foreach ( $tassili_chips as $tassili_chip ) : ?>
						<a class="active-chip" href="<?php echo esc_url( $tassili_chip['url'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: filter name. */ __( 'Remove filter: %s', 'tassili-immobilier' ), $tassili_chip['label'] ) ); ?>">
							<?php echo esc_html( $tassili_chip['label'] ); ?><?php tassili_the_icon( 'close' ); ?>
						</a>
					<?php endforeach; ?>
					<?php if ( count( $tassili_chips ) > 1 ) : ?>
						<a class="clear-all" href="<?php echo esc_url( $tassili_action ); ?>" data-reset><?php esc_html_e( 'Clear all', 'tassili-immobilier' ); ?></a>
					<?php endif; ?>
				</div>

				<?php if ( have_posts() ) : ?>
					<div id="results" class="card-grid<?php echo 'list' === $tassili_f['view'] ? ' list' : ''; ?>">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/card', 'property' );
						endwhile;
						?>
					</div>
					<?php tassili_the_pagination(); ?>
					<div class="alert-box" id="alert">
						<?php tassili_the_icon( 'wa', 'ic-lg' ); ?>
						<div>
							<h3><?php esc_html_e( 'Get new properties on WhatsApp', 'tassili-immobilier' ); ?></h3>
							<p><?php esc_html_e( 'We send you the properties that match this search, nothing else.', 'tassili-immobilier' ); ?></p>
						</div>
						<a class="btn btn-wa" id="alert-btn" href="<?php echo esc_url( tassili_whatsapp_url( sprintf( /* translators: %s: description of the search. */ __( 'Hello Tassili Immobilier, I am looking for: %s. Could you let me know when a property matches?', 'tassili-immobilier' ), implode( ', ', array_merge( array( $tassili_title ), wp_list_pluck( $tassili_chips, 'label' ) ) ) ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Create an alert', 'tassili-immobilier' ); ?></a>
					</div>
				<?php else : ?>
					<div class="empty" id="empty">
						<?php tassili_the_icon( 'search' ); ?>
						<h2><?php esc_html_e( 'No property matches your search', 'tassili-immobilier' ); ?></h2>
						<p><?php esc_html_e( 'Widen your criteria, or let us tell you as soon as a property matches.', 'tassili-immobilier' ); ?></p>
						<a class="btn btn-primary" href="<?php echo esc_url( $tassili_action ); ?>" data-reset><?php esc_html_e( 'Reset', 'tassili-immobilier' ); ?></a>
					</div>
				<?php endif; ?>
				</div>
			</section>
		</div>
	</div>
</main>
