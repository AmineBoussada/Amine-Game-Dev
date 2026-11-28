<?php
/**
 * Déco Store Child theme bootstrap.
 *
 * @package DecoStoreChild
 */

defined( 'ABSPATH' ) || exit;

define( 'DS_CHILD_VERSION', '0.3.0' );
define( 'DS_CHILD_DIR', get_stylesheet_directory() );
define( 'DS_CHILD_URI', get_stylesheet_directory_uri() );

require_once DS_CHILD_DIR . '/inc/setup.php';
require_once DS_CHILD_DIR . '/inc/enqueue.php';
require_once DS_CHILD_DIR . '/inc/customizer.php';
require_once DS_CHILD_DIR . '/inc/icons.php';
require_once DS_CHILD_DIR . '/inc/template-tags.php';
require_once DS_CHILD_DIR . '/inc/woocommerce.php';
require_once DS_CHILD_DIR . '/inc/elementor.php';
