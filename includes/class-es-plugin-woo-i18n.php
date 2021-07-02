<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/srgoogle23/es-plugin-woo
 * @since      1.0.0
 *
 * @package    Es_Plugin_Woo
 * @subpackage Es_Plugin_Woo/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Es_Plugin_Woo
 * @subpackage Es_Plugin_Woo/includes
 * @author     srgoogle23 <contato@leonardoliveira.com>
 */
class Es_Plugin_Woo_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'es-plugin-woo',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
