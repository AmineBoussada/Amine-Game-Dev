<?php
/**
 * Plugin Name:       Déco Store Core
 * Description:       Business logic for the Déco Store shop: landing pages, cash-on-delivery orders, offers, delivery, inventory and reports. Works with WooCommerce and Elementor Free; Elementor Pro is never required.
 * Version:           0.4.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Déco Store
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       deco-store-core
 * Domain Path:       /languages
 *
 * @package DecoStore
 */

defined( 'ABSPATH' ) || exit;

define( 'DS_CORE_VERSION', '0.4.0' );
define( 'DS_CORE_FILE', __FILE__ );
define( 'DS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'DS_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'DS_CORE_MIN_PHP', '7.4' );
define( 'DS_CORE_MIN_WP', '6.4' );
define( 'DS_CORE_MIN_WC', '8.0' );

require_once DS_CORE_DIR . 'src/Core/Autoloader.php';
\DecoStore\Core\Autoloader::register( 'DecoStore\\', DS_CORE_DIR . 'src/' );

register_activation_hook( __FILE__, array( '\DecoStore\Core\Activator', 'activate' ) );

// High-Performance Order Storage: declared before WooCommerce initialises.
add_action( 'before_woocommerce_init', array( '\DecoStore\Core\Plugin', 'declare_woocommerce_compatibility' ) );

// WooCommerce loads after this file (alphabetical order), so boot on plugins_loaded.
add_action( 'plugins_loaded', array( '\DecoStore\Core\Plugin', 'boot' ), 20 );
