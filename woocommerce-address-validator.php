<?php
/**
 * Plugin Name: WooCommerce Address Validator
 * Plugin URI: https://www.byteplant.com/address-validator/
 * Description: Validate billing and shipping addresses in WooCommerce.
 * Version: 3.3
 * Author: Byteplant
 * Author URI: https://www.byteplant.com/address-validator/
 * License: GPL2
 * Text-Domain: woo-address-validator
 *
 * @package WCAV
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
$plugin_version = $plugin_data['Version'];

define ( 'WCAV_PLUGIN_CURRENT_VERSION', $plugin_version );

/**
 * Load the plugin.
 */
function wcav_load() {
	load_plugin_textdomain(
		'woo-address-validator',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	require_once( dirname( __FILE__ ) . '/src/Plugin.php' );
	$plugin = new WCAV_Plugin();
	$plugin->setup();
}
add_action( 'after_setup_theme', 'wcav_load' );
