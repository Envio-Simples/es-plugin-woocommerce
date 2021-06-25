<?php

require_once "class-es-plugin-woocommerce-api.php";

/**
 * Main Class of Plugin
 *
 * @link       https://github.com/Envio-Simples/es-plugin-woocommerce
 * @since      1.0.0
 *
 * @package    Es_Plugin_Woocommerce
 * @subpackage Es_Plugin_Woocommerce/includes
 */

/**
 * Main Class of Plugin
 *
 * @since      1.0.0
 * @package    Es_Plugin_Woocommerce
 * @subpackage Es_Plugin_Woocommerce/includes
 * @author     https://github.com/Envio-Simples/es-plugin-woocommerce <contato@ecomd.com.br>
 */
class Es_Plugin_Woocommerce_main
{

    public function woocommerce_enviosimples_logger($message)
    {
        $log = wc_get_logger();
        $context = array('source' => 'envio_simples');
        $log->debug($message, $context);
        return;
    }

    
}
