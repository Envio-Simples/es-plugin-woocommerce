<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Envio-Simples/es-plugin-woocommerce
 * @since             1.0.0
 * @package           Es_Plugin_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Envio Simples 
 * Plugin URI:        Thales Matoso, srgoogle23
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           2.0.0
 * Author:            https://github.com/Envio-Simples/es-plugin-woocommerce
 * Author URI:        https://github.com/Envio-Simples/es-plugin-woocommerce
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       es-plugin-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
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
 * The code that runs during plugin activation.
 * This action is documented in includes/class-es-plugin-woocommerce-activator.php
 */
function activate_es_plugin_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-es-plugin-woocommerce-activator.php';
	Es_Plugin_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-es-plugin-woocommerce-deactivator.php
 */
function deactivate_es_plugin_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-es-plugin-woocommerce-deactivator.php';
	Es_Plugin_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_es_plugin_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_es_plugin_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-es-plugin-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_es_plugin_woocommerce() {

	$plugin = new Es_Plugin_Woocommerce();
	$plugin->run();

}
run_es_plugin_woocommerce();
