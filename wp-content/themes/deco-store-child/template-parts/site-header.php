<?php
/**
 * Site header: announcement bar, main bar, search row and mobile drawer.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

$ds_announcement = ds_child_get( 'announcement' );
$ds_phone_url    = ds_child_phone_url();
$ds_wa_url       = ds_child_whatsapp_url();
$ds_show_search  = (bool) ds_child_get( 'show_search' );
$ds_show_cart    = ds_child_has_woocommerce() && (bool) ds_child_get( 'show_cart' );
?>
<?php if ( '' !== $ds_announcement ) : ?>
	<aside class="ds-announcement" aria-label="<?php esc_attr_e( 'إعلان', 'deco-store-child' ); ?>">
		<div class="ds-container"><?php echo esc_html( $ds_announcement ); ?></div>
	</aside>
<?php endif; ?>

<header id="site-header" class="ds-header" data-ds-header>
	<div class="ds-header__bar ds-container">
		<button type="button" class="ds-icon-btn ds-header__menu-toggle" aria-controls="ds-drawer" aria-expanded="false" data-ds-drawer-open>
			<?php ds_child_the_icon( 'menu' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'فتح القائمة', 'deco-store-child' ); ?></span>
		</button>

		<div class="ds-header__brand">
			<?php ds_child_the_logo(); ?>
		</div>

		<nav class="ds-nav ds-header__nav" aria-label="<?php esc_attr_e( 'القائمة الرئيسية', 'deco-store-child' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'container'      => false,
					'menu_id'        => 'ds-primary-menu',
					'menu_class'     => 'ds-nav__list',
					'depth'          => 2,
					'fallback_cb'    => 'ds_child_menu_fallback',
				)
			);
			?>
		</nav>

		<div class="ds-header__actions">
			<?php if ( '' !== $ds_phone_url ) : ?>
				<a class="ds-header__phone" href="<?php echo esc_url( $ds_phone_url, array( 'tel' ) ); ?>">
					<?php ds_child_the_icon( 'phone' ); ?>
					<span dir="ltr"><?php echo esc_html( ds_child_get( 'phone' ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $ds_show_search ) : ?>
				<button type="button" class="ds-icon-btn" aria-controls="ds-search-row" aria-expanded="false" data-ds-search-toggle>
					<?php ds_child_the_icon( 'search' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'بحث', 'deco-store-child' ); ?></span>
				</button>
			<?php endif; ?>
			<?php
			if ( $ds_show_cart ) {
				echo ds_child_cart_link_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside ds_child_cart_link_html().
			}
			?>
		</div>
	</div>

	<?php if ( $ds_show_search ) : ?>
		<div class="ds-header__search" id="ds-search-row" hidden>
			<div class="ds-container">
				<?php get_search_form(); ?>
			</div>
		</div>
	<?php endif; ?>
</header>

<div class="ds-drawer" id="ds-drawer" hidden data-ds-drawer>
	<div class="ds-drawer__overlay" data-ds-drawer-close></div>
	<div class="ds-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'القائمة', 'deco-store-child' ); ?>" tabindex="-1">
		<div class="ds-drawer__head">
			<?php ds_child_the_logo(); ?>
			<button type="button" class="ds-icon-btn" data-ds-drawer-close>
				<?php ds_child_the_icon( 'close' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'إغلاق القائمة', 'deco-store-child' ); ?></span>
			</button>
		</div>

		<?php if ( $ds_show_search ) : ?>
			<div class="ds-drawer__search"><?php get_search_form(); ?></div>
		<?php endif; ?>

		<nav class="ds-drawer__nav" aria-label="<?php esc_attr_e( 'قائمة الجوال', 'deco-store-child' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'container'      => false,
					'menu_id'        => 'ds-drawer-menu',
					'menu_class'     => 'ds-drawer__list',
					'depth'          => 2,
					'fallback_cb'    => 'ds_child_menu_fallback',
				)
			);
			?>
		</nav>

		<?php if ( '' !== $ds_phone_url || '' !== $ds_wa_url ) : ?>
			<div class="ds-drawer__contact">
				<?php if ( '' !== $ds_phone_url ) : ?>
					<a class="ds-btn ds-btn--secondary" href="<?php echo esc_url( $ds_phone_url, array( 'tel' ) ); ?>">
						<?php ds_child_the_icon( 'phone' ); ?>
						<span><?php esc_html_e( 'اتصل بنا', 'deco-store-child' ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( '' !== $ds_wa_url ) : ?>
					<a class="ds-btn ds-btn--secondary" href="<?php echo esc_url( $ds_wa_url ); ?>" rel="noopener">
						<?php ds_child_the_icon( 'whatsapp' ); ?>
						<span><?php esc_html_e( 'واتساب', 'deco-store-child' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
