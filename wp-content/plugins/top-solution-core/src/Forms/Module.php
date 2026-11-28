<?php
/**
 * Forms module wiring.
 *
 * @package TopSolution
 */

namespace TopSolution\Forms;

use TopSolution\Admin\FormsPage;
use TopSolution\Core\Capabilities;
use TopSolution\Rest\GeoController;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the post type, REST routes, shortcode and admin screen.
 */
class Module {

	/**
	 * Hook everything.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( FormRepository::class, 'register_post_type' ), 5 );
		add_shortcode( 'ts_form', array( __CLASS__, 'shortcode' ) );
		GeoController::init();
		if ( is_admin() ) {
			add_action( 'ts_core_admin_menu', array( FormsPage::class, 'register_menu' ) );
		}
	}

	/**
	 * [ts_form id="N"]. In this phase a form can only be previewed (nothing is
	 * sent), so the shortcode renders only for people who manage forms. The
	 * order system (Phase 5) and the landing pages (Phase 6) embed forms
	 * through FormRenderer directly.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		if ( ! current_user_can( Capabilities::MANAGE_FORMS ) ) {
			return '';
		}
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'ts_form' );
		$form = FormRepository::get( (int) $atts['id'] ? (int) $atts['id'] : FormRepository::default_id() );
		return $form ? FormRenderer::render( $form, array( 'preview' => true ) ) : '';
	}
}
