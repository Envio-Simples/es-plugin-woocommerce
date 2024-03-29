<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/Envio-Simples/es-plugin-woocommerce
 * @since      1.0.0
 *
 * @package    Es_Plugin_Woocommerce
 * @subpackage Es_Plugin_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Es_Plugin_Woocommerce
 * @subpackage Es_Plugin_Woocommerce/includes
 * @author     https://github.com/Envio-Simples/es-plugin-woocommerce <contato@ecomd.com.br>
 */
class Es_Plugin_Woocommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		
		global $wp_version;

		if (version_compare($wp_version,WC_ENVIOSIMPLES_REQUIRED_VERSION,'<')){
			wp_die("Este plugin requer no mínimo a versão " . WC_ENVIOSIMPLES_REQUIRED_VERSION . " do Wordpress");
		}
	
		if (!function_exists('curl_version')){
			wp_die("Para a utilização deste plugin é obrigatória a habilitação da extensão CURL do PHP");
		}
	}

}
