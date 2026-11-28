<?php
/**
 * Offers module wiring.
 *
 * @package TopSolution
 */

namespace TopSolution\Offers;

use TopSolution\Admin\OffersPage;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the post type and the admin screen.
 */
class Module {

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( OfferRepository::class, 'register_post_type' ), 5 );
		if ( is_admin() ) {
			add_action( 'ts_core_admin_menu', array( OffersPage::class, 'register_menu' ) );
		}
	}
}
