<?php
/**
 * Plugin Name:          WooCommerce Envio Simples 
 * Description:          Adds Envio Simples shipping methods to your WooCommerce store.
 * Author:               Augesystems
 * Author URI:           https://www.augesystems.com.br
 * Version:              1.0.0
 * License:              GPLv2 or later 
 * WC requires at least: 3.0.0
 * WC tested up to:      3.4.4
 *
 * Woocommerce Envio Simples is a plugin for woocoomerce create to add the Envio Simples shipping methods to your store.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooCommerce Correios. If not, see
 * <https://www.gnu.org/licenses/gpl-2.0.txt>.
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

define( 'WC_ENVIOSIMPLES_VERSION', '1.0.0' );

define( 'WC_ENVIOSIMPLES_PLUGIN_FILE', __FILE__ );
define('WC_ENVIOSIMPLES_DIR',plugin_dir_path(__FILE__));
define('WC_ENVIOSIMPLES_REQUIRED_VERSION','4.9.5');

if ( ! class_exists( 'WC_woocommerce_enviosimples' ) ) {
	include_once WC_ENVIOSIMPLES_DIR . '/includes/class-wc-enviosimples.php';	
}

register_activation_hook(__FILE__,'WC_ENVIOSIMPLES_activation');

function WC_ENVIOSIMPLES_activation(){

	global $wp_version;

	if (version_compare($wp_version,WC_ENVIOSIMPLES_REQUIRED_VERSION,'<')){
		wp_die("Este plugin requer no mínimo a versão " . WC_ENVIOSIMPLES_REQUIRED_VERSION . " do Wordpress");
	}

	if (!function_exists('curl_version')){
		wp_die("Para a utilização deste plugin é obrigatória a habilitação da extensão CURL do PHP");
	}
}
