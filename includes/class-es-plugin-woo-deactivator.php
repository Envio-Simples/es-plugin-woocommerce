<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/srgoogle23/es-plugin-woo
 * @since      1.0.0
 *
 * @package    Es_Plugin_Woo
 * @subpackage Es_Plugin_Woo/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Es_Plugin_Woo
 * @subpackage Es_Plugin_Woo/includes
 * @author     srgoogle23 <contato@leonardoliveira.com>
 */
class Es_Plugin_Woo_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}options where option_name like'%enviosimples%'" );
	}

}
