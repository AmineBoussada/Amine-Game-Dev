<?php
/**
 * Tassili Immobilier theme functions.
 *
 * This file only defines constants and loads the modules in /inc.
 *
 * @package Tassili_Immobilier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TASSILI_VERSION', '1.2.0' );
define( 'TASSILI_DIR', trailingslashit( get_template_directory() ) );
define( 'TASSILI_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * Modules, in load order. Language comes first because it must hook the locale filter
 * before WordPress loads translations.
 */
$tassili_modules = array(
	'language',
	'icons',
	'helpers',
	'setup',
	'enqueue',
	'post-types',
	'taxonomies',
	'meta-boxes',
	'admin',
	'customizer',
	'query-filters',
	'template-tags',
	'nav',
	'rest',
	'forms',
	'elementor',
	'demo-import',
);

foreach ( $tassili_modules as $tassili_module ) {
	require_once TASSILI_DIR . 'inc/' . $tassili_module . '.php';
}
unset( $tassili_modules, $tassili_module );
