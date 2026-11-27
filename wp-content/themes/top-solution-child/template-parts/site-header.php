<?php
/**
 * Site header: announcement bar, main bar, search row and mobile drawer.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

$ts_announcement = ts_child_get( 'announcement' );
$ts_phone_url    = ts_child_phone_url();
$ts_wa_url       = ts_child_whatsapp_url();
$ts_show_search  = (bool) ts_child_get( 'show_search' );
$ts_show_cart    = ts_child_has_woocommerce() && (bool) ts_child_get( 'show_cart' );
?>
<?php if ( '' !== $ts_announcement ) : ?>
	<aside class="ts-announcement" aria-label="<?php esc_attr_e( 'إعلان', 'top-solution-child' ); ?>">
		<div class="ts-container"><?php echo esc_html( $ts_announcement ); ?></div>
	</aside>
<?php endif; ?>

<header id="site-header" class="ts-header" data-ts-header>
	<div class="ts-header__bar ts-container">
		<button type="button" class="ts-icon-btn ts-header__menu-toggle" aria-controls="ts-drawer" aria-expanded="false" data-ts-drawer-open>
			<?php ts_child_the_icon( 'menu' ); ?>
			<span class="screen-reader-text"><?php esc_html_e( 'فتح القائمة', 'top-solution-child' ); ?></span>
		</button>

		<div class="ts-header__brand">
			<?php ts_child_the_logo(); ?>
		</div>

		<nav class="ts-nav ts-header__nav" aria-label="<?php esc_attr_e( 'القائمة الرئيسية', 'top-solution-child' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'container'      => false,
					'menu_id'        => 'ts-primary-menu',
					'menu_class'     => 'ts-nav__list',
					'depth'          => 2,
					'fallback_cb'    => 'ts_child_menu_fallback',
				)
			);
			?>
		</nav>

		<div class="ts-header__actions">
			<?php if ( '' !== $ts_phone_url ) : ?>
				<a class="ts-header__phone" href="<?php echo esc_url( $ts_phone_url, array( 'tel' ) ); ?>">
					<?php ts_child_the_icon( 'phone' ); ?>
					<span dir="ltr"><?php echo esc_html( ts_child_get( 'phone' ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $ts_show_search ) : ?>
				<button type="button" class="ts-icon-btn" aria-controls="ts-search-row" aria-expanded="false" data-ts-search-toggle>
					<?php ts_child_the_icon( 'search' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'بحث', 'top-solution-child' ); ?></span>
				</button>
			<?php endif; ?>
			<?php
			if ( $ts_show_cart ) {
				echo ts_child_cart_link_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside ts_child_cart_link_html().
			}
			?>
		</div>
	</div>

	<?php if ( $ts_show_search ) : ?>
		<div class="ts-header__search" id="ts-search-row" hidden>
			<div class="ts-container">
				<?php get_search_form(); ?>
			</div>
		</div>
	<?php endif; ?>
</header>

<div class="ts-drawer" id="ts-drawer" hidden data-ts-drawer>
	<div class="ts-drawer__overlay" data-ts-drawer-close></div>
	<div class="ts-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'القائمة', 'top-solution-child' ); ?>" tabindex="-1">
		<div class="ts-drawer__head">
			<?php ts_child_the_logo(); ?>
			<button type="button" class="ts-icon-btn" data-ts-drawer-close>
				<?php ts_child_the_icon( 'close' ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'إغلاق القائمة', 'top-solution-child' ); ?></span>
			</button>
		</div>

		<?php if ( $ts_show_search ) : ?>
			<div class="ts-drawer__search"><?php get_search_form(); ?></div>
		<?php endif; ?>

		<nav class="ts-drawer__nav" aria-label="<?php esc_attr_e( 'قائمة الجوال', 'top-solution-child' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'container'      => false,
					'menu_id'        => 'ts-drawer-menu',
					'menu_class'     => 'ts-drawer__list',
					'depth'          => 2,
					'fallback_cb'    => 'ts_child_menu_fallback',
				)
			);
			?>
		</nav>

		<?php if ( '' !== $ts_phone_url || '' !== $ts_wa_url ) : ?>
			<div class="ts-drawer__contact">
				<?php if ( '' !== $ts_phone_url ) : ?>
					<a class="ts-btn ts-btn--secondary" href="<?php echo esc_url( $ts_phone_url, array( 'tel' ) ); ?>">
						<?php ts_child_the_icon( 'phone' ); ?>
						<span><?php esc_html_e( 'اتصل بنا', 'top-solution-child' ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( '' !== $ts_wa_url ) : ?>
					<a class="ts-btn ts-btn--secondary" href="<?php echo esc_url( $ts_wa_url ); ?>" rel="noopener">
						<?php ts_child_the_icon( 'whatsapp' ); ?>
						<span><?php esc_html_e( 'واتساب', 'top-solution-child' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
