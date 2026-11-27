<?php
/**
 * Site header: utility bar, main bar with navigation, and the mobile drawer.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tassili_phone = (string) tassili_mod( 'phone' );
$tassili_other = tassili_is_ar() ? 'fr' : 'ar';
$tassili_langs = tassili_supported_languages();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( ! tassili_elementor_location( 'header' ) ) : ?>
<header id="site-header" class="site-header">
	<div class="utility">
		<div class="container utility-in">
			<?php if ( '' !== $tassili_phone ) : ?>
				<span class="u-item">
					<?php tassili_the_icon( 'phone', 'ic-sm' ); ?>
					<a class="ltr" href="<?php echo esc_url( 'tel:' . tassili_tel_href( $tassili_phone ) ); ?>"><?php echo esc_html( $tassili_phone ); ?></a>
				</span>
			<?php endif; ?>
			<span class="u-item u-hours">
				<?php tassili_the_icon( 'clock', 'ic-sm' ); ?>
				<?php echo esc_html( tassili_mod_l( 'hours' ) ); ?>
			</span>
			<span class="u-spacer"></span>
			<a class="lang-btn" href="<?php echo esc_url( tassili_language_url( $tassili_other ) ); ?>" lang="<?php echo esc_attr( $tassili_other ); ?>" hreflang="<?php echo esc_attr( $tassili_other ); ?>" data-lang-switch>
				<?php tassili_the_icon( 'globe' ); ?>
				<span><?php echo esc_html( $tassili_langs[ $tassili_other ]['label'] ); ?></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Show the site in this language', 'tassili-immobilier' ); ?></span>
			</a>
		</div>
	</div>

	<div class="mainbar">
		<div class="container mainbar-in">
			<?php tassili_the_logo(); ?>

			<nav class="nav" aria-label="<?php esc_attr_e( 'Main navigation', 'tassili-immobilier' ); ?>">
				<?php tassili_the_primary_nav( 'bar' ); ?>
			</nav>

			<div class="actions">
				<a class="icon-btn" href="<?php echo esc_url( tassili_favorites_url() ); ?>" data-fav-link aria-label="<?php esc_attr_e( 'My favourites', 'tassili-immobilier' ); ?>">
					<?php tassili_the_icon( 'heart' ); ?>
					<span class="fav-count" data-fav-count hidden></span>
				</a>
				<button type="button" class="icon-btn" data-theme-toggle aria-label="<?php esc_attr_e( 'Switch to dark mode', 'tassili-immobilier' ); ?>">
					<span data-theme-icon="moon"><?php tassili_the_icon( 'moon' ); ?></span>
					<span data-theme-icon="sun" hidden><?php tassili_the_icon( 'sun' ); ?></span>
				</button>
				<a class="btn btn-gold btn-sm hide-md" href="<?php echo esc_url( tassili_cta_url() ); ?>"><?php esc_html_e( 'List my property', 'tassili-immobilier' ); ?></a>
				<a class="btn btn-wa btn-sm btn-icon hide-md" href="<?php echo esc_url( tassili_whatsapp_url( __( 'Hello Tassili Immobilier, I would like some information.', 'tassili-immobilier' ) ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Write on WhatsApp', 'tassili-immobilier' ); ?>">
					<?php tassili_the_icon( 'wa' ); ?>
				</a>
				<button type="button" class="icon-btn burger" data-drawer-open aria-expanded="false" aria-controls="drawer" aria-label="<?php esc_attr_e( 'Open the menu', 'tassili-immobilier' ); ?>">
					<?php tassili_the_icon( 'menu' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="drawer" id="drawer" hidden>
		<div class="drawer-backdrop" data-drawer-close></div>
		<div class="drawer-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'tassili-immobilier' ); ?>">
			<div class="drawer-head">
				<?php tassili_the_logo( 'logo--drawer' ); ?>
				<button type="button" class="icon-btn" data-drawer-close aria-label="<?php esc_attr_e( 'Close the menu', 'tassili-immobilier' ); ?>">
					<?php tassili_the_icon( 'close' ); ?>
				</button>
			</div>
			<?php tassili_the_primary_nav( 'drawer' ); ?>
			<div class="drawer-foot">
				<a class="btn btn-gold" href="<?php echo esc_url( tassili_cta_url() ); ?>"><?php esc_html_e( 'List my property', 'tassili-immobilier' ); ?></a>
				<a class="btn btn-wa" href="<?php echo esc_url( tassili_whatsapp_url( __( 'Hello Tassili Immobilier, I would like some information.', 'tassili-immobilier' ) ) ); ?>" target="_blank" rel="noopener">
					<?php tassili_the_icon( 'wa' ); ?><?php esc_html_e( 'Write on WhatsApp', 'tassili-immobilier' ); ?>
				</a>
				<?php if ( '' !== $tassili_phone ) : ?>
					<a class="btn btn-ghost" href="<?php echo esc_url( 'tel:' . tassili_tel_href( $tassili_phone ) ); ?>">
						<?php tassili_the_icon( 'phone' ); ?><?php esc_html_e( 'Call', 'tassili-immobilier' ); ?> <span class="ltr"><?php echo esc_html( $tassili_phone ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>
<?php endif; ?>
