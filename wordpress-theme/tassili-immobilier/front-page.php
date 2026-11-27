<?php
/**
 * Home page: hero with search, wilayas, featured listings, property types, reasons to choose
 * the agency, new developments, figures, testimonials and the seller call to action.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// A front page built with Elementor replaces the built-in sections.
if ( tassili_render_builder_page() ) {
	get_footer();
	return;
}

$tassili_hero_title = tassili_mod_l( 'hero_title' );
$tassili_hero_title = '' !== $tassili_hero_title ? $tassili_hero_title : __( 'Find the property that suits you, with the papers in order', 'tassili-immobilier' );
$tassili_types      = get_terms(
	array(
		'taxonomy'   => 'property_type',
		'hide_empty' => false,
	)
);
$tassili_tiles      = array( 'ext-villa-2', 'ext-residence-1', 'ext-residence-3', 'ext-duplex-2', 'ext-residence-2' );
?>
<main id="main" class="site-main">

	<section class="hero">
		<img class="hero-bg" src="<?php echo esc_url( tassili_hero_image_url() ); ?>" alt="" width="800" height="600" fetchpriority="high" decoding="async" />
		<div class="hero-shade"></div>
		<div class="container hero-in">
			<p class="eyebrow">
				<?php
				/* translators: %d: number of wilayas covered. */
				echo esc_html( sprintf( __( 'Real estate agency · %d wilayas', 'tassili-immobilier' ), (int) tassili_mod( 'stat_wilayas' ) ) );
				?>
			</p>
			<h1 class="hero-title"><?php echo esc_html( $tassili_hero_title ); ?></h1>
			<p class="hero-lede"><?php echo esc_html( '' !== tassili_mod_l( 'hero_lede' ) ? tassili_mod_l( 'hero_lede' ) : __( 'Apartments, villas, land and new developments, checked by our team, from Algiers to Oran.', 'tassili-immobilier' ) ); ?></p>

			<?php get_template_part( 'template-parts/home-search' ); ?>

			<ul class="hero-trust">
				<li><?php tassili_the_icon( 'shield' ); ?><span><?php esc_html_e( 'Documents checked before publication', 'tassili-immobilier' ); ?></span></li>
				<li><?php tassili_the_icon( 'key' ); ?><span>
					<?php
					printf(
						/* translators: 1: number of properties sold, 2: year founded. */
						esc_html__( '%1$s properties sold since %2$d', 'tassili-immobilier' ),
						esc_html( tassili_number( (int) tassili_mod( 'stat_sold' ) ) ),
						(int) tassili_mod( 'founded' )
					);
					?>
				</span></li>
				<li><?php tassili_the_icon( 'wa' ); ?><span><?php esc_html_e( 'WhatsApp reply within 15 minutes', 'tassili-immobilier' ); ?></span></li>
			</ul>
		</div>
	</section>

	<?php $tassili_top = tassili_top_wilayas( 5 ); ?>
	<?php if ( $tassili_top ) : ?>
		<section class="section" aria-labelledby="t-wilayas">
			<div class="container">
				<div class="section-head">
					<div>
						<h2 id="t-wilayas"><?php esc_html_e( 'Browse by wilaya', 'tassili-immobilier' ); ?></h2>
						<p><?php esc_html_e( 'Our advisers know every neighbourhood.', 'tassili-immobilier' ); ?></p>
					</div>
				</div>
				<div class="wilaya-grid">
					<?php foreach ( $tassili_top as $tassili_i => $tassili_w ) : ?>
						<a class="wilaya-tile" href="<?php echo esc_url( get_term_link( $tassili_w ) ); ?>">
							<img src="<?php echo esc_url( tassili_placeholder_url( $tassili_tiles[ $tassili_i % count( $tassili_tiles ) ] ) ); ?>" alt="" width="800" height="600" loading="lazy" />
							<span class="wt-in">
								<span class="wt-name"><?php echo esc_html( tassili_term_name( $tassili_w ) ); ?></span>
								<span class="wt-count"><?php echo esc_html( tassili_count_label( tassili_term_listing_count( $tassili_w ) ) ); ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$tassili_featured = new WP_Query(
		array(
			'post_type'      => 'property',
			'posts_per_page' => 6,
			'no_found_rows'  => true,
			'meta_query'     => array_filter( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- six rows, cached by the object cache.
				array(
					'relation' => 'AND',
					array(
						'key'   => '_tassili_featured',
						'value' => '1',
					),
					tassili_open_listing_clause(),
				)
			),
		)
	);
	?>
	<?php if ( $tassili_featured->have_posts() ) : ?>
		<section class="section section-alt" aria-labelledby="t-featured">
			<div class="container">
				<div class="section-head">
					<div>
						<h2 id="t-featured"><?php esc_html_e( 'Featured properties', 'tassili-immobilier' ); ?></h2>
						<p><?php esc_html_e( 'Chosen this week by our advisers.', 'tassili-immobilier' ); ?></p>
					</div>
					<a class="link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'property' ) ); ?>"><?php esc_html_e( 'See all properties', 'tassili-immobilier' ); ?><?php tassili_the_icon( 'arrow', 'flip' ); ?></a>
				</div>
				<div class="card-grid">
					<?php
					while ( $tassili_featured->have_posts() ) :
						$tassili_featured->the_post();
						get_template_part( 'template-parts/card', 'property' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section" aria-labelledby="t-types">
		<div class="container">
			<div class="section-head"><h2 id="t-types"><?php esc_html_e( 'What are you looking for?', 'tassili-immobilier' ); ?></h2></div>
			<div class="type-grid">
				<?php foreach ( (array) $tassili_types as $tassili_t ) : ?>
					<?php $tassili_icon = (string) get_term_meta( $tassili_t->term_id, 'tassili_icon', true ); ?>
					<a class="type-tile" href="<?php echo esc_url( get_term_link( $tassili_t ) ); ?>">
						<?php tassili_the_icon( '' !== $tassili_icon ? $tassili_icon : 'building' ); ?>
						<b><?php echo esc_html( tassili_term_name( $tassili_t ) ); ?></b>
						<span>
							<?php
							/* translators: %s: number of listings. */
							echo esc_html( sprintf( _n( '%s listing', '%s listings', (int) $tassili_t->count, 'tassili-immobilier' ), number_format_i18n( (int) $tassili_t->count ) ) );
							?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section section-alt" aria-labelledby="t-why">
		<div class="container">
			<div class="section-head"><h2 id="t-why"><?php esc_html_e( 'Why choose us', 'tassili-immobilier' ); ?></h2></div>
			<div class="why-grid">
				<div class="why-item"><span class="why-ic"><?php tassili_the_icon( 'shield' ); ?></span><h3><?php esc_html_e( 'Documents checked', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'Deed, land registry booklet and permit checked before every listing.', 'tassili-immobilier' ); ?></p></div>
				<div class="why-item"><span class="why-ic"><?php tassili_the_icon( 'calendar' ); ?></span><h3><?php esc_html_e( 'Organised visits', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'An adviser goes with you, at the times that suit you.', 'tassili-immobilier' ); ?></p></div>
				<div class="why-item"><span class="why-ic"><?php tassili_the_icon( 'doc' ); ?></span><h3><?php esc_html_e( 'Right to the notary', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'We prepare the file and stay with you until the signature.', 'tassili-immobilier' ); ?></p></div>
				<div class="why-item"><span class="why-ic"><?php tassili_the_icon( 'bolt' ); ?></span><h3><?php esc_html_e( 'Quick answers', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'On WhatsApp or by phone, in Arabic or French.', 'tassili-immobilier' ); ?></p></div>
			</div>
		</div>
	</section>

	<?php
	$tassili_new = new WP_Query(
		array(
			'post_type'      => 'property',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
			'meta_query'     => array_filter( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- three rows.
				array(
					'relation' => 'AND',
					array(
						'key'   => '_tassili_is_new',
						'value' => '1',
					),
					tassili_open_listing_clause(),
				)
			),
		)
	);
	?>
	<?php if ( $tassili_new->have_posts() ) : ?>
		<section class="section" aria-labelledby="t-new">
			<div class="container">
				<div class="section-head">
					<div>
						<h2 id="t-new"><?php esc_html_e( 'New developments', 'tassili-immobilier' ); ?></h2>
						<p><?php esc_html_e( 'Buy off plan with staged payments.', 'tassili-immobilier' ); ?></p>
					</div>
					<a class="link-arrow" href="<?php echo esc_url( tassili_archive_url( array( 'neuf' => '1' ) ) ); ?>"><?php esc_html_e( 'See all properties', 'tassili-immobilier' ); ?><?php tassili_the_icon( 'arrow', 'flip' ); ?></a>
				</div>
				<div class="card-grid">
					<?php
					while ( $tassili_new->have_posts() ) :
						$tassili_new->the_post();
						get_template_part( 'template-parts/card', 'property' );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section-alt" aria-labelledby="t-testi">
		<div class="container">
			<div class="figures">
				<?php foreach ( tassili_stats() as $tassili_stat ) : ?>
					<div class="figure"><b class="ltr"><?php echo esc_html( $tassili_stat['value'] ); ?></b><span><?php echo esc_html( $tassili_stat['label'] ); ?></span></div>
				<?php endforeach; ?>
			</div>

			<?php
			$tassili_testimonials = new WP_Query(
				array(
					'post_type'      => 'testimonial',
					'posts_per_page' => 3,
					'orderby'        => array(
						'menu_order' => 'ASC',
						'date'       => 'DESC',
					),
					'no_found_rows'  => true,
				)
			);
			?>
			<?php if ( $tassili_testimonials->have_posts() ) : ?>
				<div class="section-head"><h2 id="t-testi"><?php esc_html_e( 'They trusted us', 'tassili-immobilier' ); ?></h2></div>
				<div class="testi-grid">
					<?php
					while ( $tassili_testimonials->have_posts() ) :
						$tassili_testimonials->the_post();
						$tassili_tid   = get_the_ID();
						$tassili_quote = tassili_localized_post_text( $tassili_tid, 'quote', wp_strip_all_tags( get_the_content() ) );
						$tassili_place = tassili_localized_post_text( $tassili_tid, 'place', (string) get_post_meta( $tassili_tid, '_tassili_place', true ) );
						?>
						<figure class="testi">
							<?php tassili_the_icon( 'quote' ); ?>
							<blockquote><?php echo esc_html( $tassili_quote ); ?></blockquote>
							<footer>
								<span class="avatar"><?php echo esc_html( tassili_initials( get_the_title() ) ); ?></span>
								<div><b><?php the_title(); ?></b><span class="muted"><?php echo esc_html( $tassili_place ); ?></span></div>
							</footer>
						</figure>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<div class="cta-band">
				<div>
					<h2><?php esc_html_e( 'Selling or letting a property?', 'tassili-immobilier' ); ?></h2>
					<p><?php esc_html_e( 'Free valuation within 48 hours, professional photos and exposure to buyers who are already qualified.', 'tassili-immobilier' ); ?></p>
				</div>
				<div class="cta-actions">
					<a class="btn btn-gold btn-lg" href="<?php echo esc_url( tassili_contact_url( 'estimate' ) ); ?>"><?php esc_html_e( 'Value my property', 'tassili-immobilier' ); ?></a>
					<a class="btn btn-light btn-lg" href="<?php echo esc_url( tassili_whatsapp_url( __( 'Hello Tassili Immobilier, I would like to have my property valued.', 'tassili-immobilier' ) ) ); ?>" target="_blank" rel="noopener"><?php tassili_the_icon( 'wa' ); ?><span><?php esc_html_e( 'WhatsApp', 'tassili-immobilier' ); ?></span></a>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
