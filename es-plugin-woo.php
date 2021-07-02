<?php

/**
 *
 * @link              https://github.com/srgoogle23/es-plugin-woo
 * @since             1.0.0
 * @package           Es_Plugin_Woo
 *
 * @wordpress-plugin
 * Plugin Name:       Envio Simples
 * Plugin URI:        https://github.com/srgoogle23/es-plugin-woo
 * Description:       Adds Envio Simples shipping methods to your store.
 * Version:           2.0.0
 * Author:            srgoogle23
 * Author URI:        https://github.com/srgoogle23/es-plugin-woo
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       es-plugin-woo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ES_PLUGIN_WOOCOMMERCE_VERSION', '2.0.0' );

/**
 * Currently plugin dir.
 */
define('WC_ENVIOSIMPLES_DIR',plugin_dir_path(__FILE__));

/**
 * Currently required wp version.
 */
define('WC_ENVIOSIMPLES_REQUIRED_VERSION','4.9.5');

/**
 * Currently plugin URL.
 */
define('WC_ENVIOSIMPLES_URL',plugin_dir_url( __FILE__ ));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-es-plugin-woo-activator.php
 */
function activate_es_plugin_woo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-es-plugin-woo-activator.php';
	Es_Plugin_Woo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-es-plugin-woo-deactivator.php
 */
function deactivate_es_plugin_woo() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-es-plugin-woo-deactivator.php';
	Es_Plugin_Woo_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_es_plugin_woo' );
register_deactivation_hook( __FILE__, 'deactivate_es_plugin_woo' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-es-plugin-woo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_es_plugin_woo() {

	$plugin = new Es_Plugin_Woo();
	$plugin->run();

}
run_es_plugin_woo();
