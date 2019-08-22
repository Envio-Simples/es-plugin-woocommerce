<?php
/**
 * WooCommerce Envio Simples Uninstall
 *
 * @package WooCommerce_enviosimples/Uninstaller
 * @since   3.0.0
 * @version 3.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DELETE FROM {$wpdb->prefix}options where option_name like'%enviosimples%'" );