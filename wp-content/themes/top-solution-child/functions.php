<?php
/**
 * Top Solution Child theme bootstrap.
 *
 * @package TopSolutionChild
 */

defined( 'ABSPATH' ) || exit;

define( 'TS_CHILD_VERSION', '0.2.0' );
define( 'TS_CHILD_DIR', get_stylesheet_directory() );
define( 'TS_CHILD_URI', get_stylesheet_directory_uri() );

require_once TS_CHILD_DIR . '/inc/setup.php';
require_once TS_CHILD_DIR . '/inc/enqueue.php';
require_once TS_CHILD_DIR . '/inc/customizer.php';
require_once TS_CHILD_DIR . '/inc/icons.php';
require_once TS_CHILD_DIR . '/inc/template-tags.php';
require_once TS_CHILD_DIR . '/inc/woocommerce.php';
require_once TS_CHILD_DIR . '/inc/elementor.php';
