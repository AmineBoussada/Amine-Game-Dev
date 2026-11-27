<?php
/**
 * Template Name: About the agency
 * Template Post Type: page
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( tassili_render_builder_page() ) {
	get_footer();
	return;
}

$tassili_agents = new WP_Query(
	array(
		'post_type'      => 'agent',
		'posts_per_page' => 12,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'ID'         => 'ASC',
		),
		'no_found_rows'  => true,
	)
);
?>
<main id="main" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<div class="section-alt">
			<div class="container about-hero">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'The agency', 'tassili-immobilier' ); ?></p>
					<h1><?php the_title(); ?></h1>
					<p class="lede">
						<?php
						printf(
							/* translators: 1: year founded, 2: number of wilayas covered. */
							esc_html__( 'Founded in %1$d, we help buyers, sellers and tenants in %2$d wilayas. Our rule has never changed: we only publish properties whose documents we have seen.', 'tassili-immobilier' ),
							(int) tassili_mod( 'founded' ),
							(int) tassili_mod( 'stat_wilayas' )
						);
						?>
					</p>
				</div>
				<img src="<?php echo esc_url( tassili_placeholder_url( 'about' ) ); ?>" alt="" width="800" height="600" />
			</div>
		</div>

		<?php if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
			<section class="section">
				<div class="container"><div class="entry-content"><?php the_content(); ?></div></div>
			</section>
		<?php endif; ?>

		<section class="section">
			<div class="container">
				<div class="figures">
					<?php foreach ( tassili_stats() as $tassili_stat ) : ?>
						<div class="figure"><b class="ltr"><?php echo esc_html( $tassili_stat['value'] ); ?></b><span><?php echo esc_html( $tassili_stat['label'] ); ?></span></div>
					<?php endforeach; ?>
				</div>
				<div class="section-head">
					<div>
						<h2><?php esc_html_e( 'Our method', 'tassili-immobilier' ); ?></h2>
						<p><?php esc_html_e( 'The same steps for every property, from valuation to handing over the keys.', 'tassili-immobilier' ); ?></p>
					</div>
				</div>
				<ol class="steps">
					<li><h3><?php esc_html_e( 'Valuation', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'We visit the property and set a price based on recent sales in the area.', 'tassili-immobilier' ); ?></p></li>
					<li><h3><?php esc_html_e( 'Document check', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'Deed, land registry booklet, permit and co-ownership status.', 'tassili-immobilier' ); ?></p></li>
					<li><h3><?php esc_html_e( 'Visits', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'Organised visits, with a report sent on WhatsApp.', 'tassili-immobilier' ); ?></p></li>
					<li><h3><?php esc_html_e( 'Notary', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'We prepare the file and the appointment at the notary.', 'tassili-immobilier' ); ?></p></li>
					<li><h3><?php esc_html_e( 'Handing over the keys', 'tassili-immobilier' ); ?></h3><p><?php esc_html_e( 'Inventory of fixtures and transfer of the meters.', 'tassili-immobilier' ); ?></p></li>
				</ol>
			</div>
		</section>

		<?php if ( $tassili_agents->have_posts() ) : ?>
			<section class="section section-alt" aria-labelledby="t-team">
				<div class="container">
					<div class="section-head">
						<div>
							<h2 id="t-team"><?php esc_html_e( 'The team', 'tassili-immobilier' ); ?></h2>
							<p><?php esc_html_e( 'One contact person from the first call to the signature.', 'tassili-immobilier' ); ?></p>
						</div>
					</div>
					<div class="team-grid">
						<?php
						while ( $tassili_agents->have_posts() ) :
							$tassili_agents->the_post();
							$tassili_a = tassili_get_agent( get_the_ID() );
							if ( ! $tassili_a ) {
								continue;
							}
							?>
							<article class="member">
								<?php if ( $tassili_a['photo'] ) : ?>
									<?php
									echo wp_get_attachment_image(
										$tassili_a['photo'],
										'tassili-avatar',
										false,
										array(
											'class' => 'avatar',
											'alt'   => '',
										)
									);
									?>
								<?php else : ?>
									<span class="avatar"><?php echo esc_html( tassili_initials( $tassili_a['name'] ) ); ?></span>
								<?php endif; ?>
								<div>
									<b><?php echo esc_html( $tassili_a['name'] ); ?></b>
									<p class="role"><?php echo esc_html( $tassili_a['role'] ); ?></p>
								</div>
								<?php if ( '' !== $tassili_a['langs'] ) : ?>
									<p class="langs">
										<?php
										/* translators: %s: languages spoken. */
										echo esc_html( sprintf( __( 'Languages: %s', 'tassili-immobilier' ), $tassili_a['langs'] ) );
										?>
									</p>
								<?php endif; ?>
								<a class="btn btn-wa btn-sm" href="<?php echo esc_url( tassili_whatsapp_url( __( 'Hello Tassili Immobilier, I would like some information.', 'tassili-immobilier' ), $tassili_a['whatsapp'] ) ); ?>" target="_blank" rel="noopener"><?php tassili_the_icon( 'wa' ); ?><?php esc_html_e( 'WhatsApp', 'tassili-immobilier' ); ?></a>
							</article>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="section" id="mentions" aria-labelledby="t-legal">
			<div class="container">
				<div class="section-head"><h2 id="t-legal"><?php esc_html_e( 'Legal information', 'tassili-immobilier' ); ?></h2></div>
				<dl class="legal-list">
					<div><dt><?php esc_html_e( 'Head office', 'tassili-immobilier' ); ?></dt><dd><?php echo esc_html( tassili_mod_l( 'address' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Trade register', 'tassili-immobilier' ); ?></dt><dd class="ltr"><?php echo esc_html( (string) tassili_mod( 'rc' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Tax identification number', 'tassili-immobilier' ); ?></dt><dd class="ltr"><?php echo esc_html( (string) tassili_mod( 'nif' ) ); ?></dd></div>
				</dl>
				<div id="confidentialite" class="read" style="margin-top:var(--s-6)">
					<h2 style="font-size:1.4rem;margin-bottom:8px"><?php esc_html_e( 'Privacy', 'tassili-immobilier' ); ?></h2>
					<p class="muted" style="max-width:68ch"><?php esc_html_e( 'The information sent through our forms is used only to answer your request. Your favourite properties stay saved on your own device.', 'tassili-immobilier' ); ?></p>
				</div>
			</div>
		</section>

		<section class="section" style="padding-top:0">
			<div class="container">
				<div class="cta-band">
					<div>
						<h2><?php esc_html_e( 'Come and meet us at the agency', 'tassili-immobilier' ); ?></h2>
						<p><?php esc_html_e( 'Without appointment Sunday to Thursday, or by appointment on Saturday morning.', 'tassili-immobilier' ); ?></p>
					</div>
					<div class="cta-actions"><a class="btn btn-gold btn-lg" href="<?php echo esc_url( tassili_contact_url() ); ?>"><?php esc_html_e( 'Contact us', 'tassili-immobilier' ); ?></a></div>
				</div>
			</div>
		</section>

	<?php endwhile; ?>
</main>
<?php
get_footer();
