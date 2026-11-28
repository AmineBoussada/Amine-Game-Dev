<?php
/**
 * Plugin Name:       Top Solution Core
 * Description:       Business logic for the Top Solution store: landing pages, cash-on-delivery orders, offers, delivery, inventory and reports. Works with WooCommerce and Elementor Free; Elementor Pro is never required.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Top Solution
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       top-solution-core
 * Domain Path:       /languages
 *
 * @package TopSolution
 */

defined( 'ABSPATH' ) || exit;

define( 'TS_CORE_VERSION', '0.1.0' );
define( 'TS_CORE_FILE', __FILE__ );
define( 'TS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'TS_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'TS_CORE_MIN_PHP', '7.4' );
define( 'TS_CORE_MIN_WP', '6.4' );
define( 'TS_CORE_MIN_WC', '8.0' );

require_once TS_CORE_DIR . 'src/Core/Autoloader.php';
\TopSolution\Core\Autoloader::register( 'TopSolution\\', TS_CORE_DIR . 'src/' );

register_activation_hook( __FILE__, array( '\TopSolution\Core\Activator', 'activate' ) );

// High-Performance Order Storage: declared before WooCommerce initialises.
add_action( 'before_woocommerce_init', array( '\TopSolution\Core\Plugin', 'declare_woocommerce_compatibility' ) );

// WooCommerce loads after this file (alphabetical order), so boot on plugins_loaded.
add_action( 'plugins_loaded', array( '\TopSolution\Core\Plugin', 'boot' ), 20 );
