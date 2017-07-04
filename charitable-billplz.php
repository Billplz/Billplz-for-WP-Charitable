<?php
/**
 * Plugin Name: Billplz for WP Charitable
 * Plugin URI: https://github.com/wzul/billplz-for-wp-charitable
 * Description: Billplz Payment Gateway | Accept Payment using all participating FPX Banking Channels. <a href="https://www.billplz.com/join/8ant7x743awpuaqcxtqufg" target="_blank">Sign up Now</a>.
 * Author: Wanzul Hosting Enterprise
 * Author URI: http://www.wanzul-hosting.com/
 * Version: 3.0
 * License: GPLv3
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Load plugin class, but only if Charitable is found and activated.
 * 
 */
function charitable_billplz_load() {	
	require_once( 'includes/class-charitable-billplz.php' );

	$has_dependencies = true;

	/* Check for Charitable */
	if ( ! class_exists( 'Charitable' ) ) {

		if ( ! class_exists( 'Charitable_Extension_Activation' ) ) {

			require_once 'includes/class-charitable-extension-activation.php';

		}

		$activation = new Charitable_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		$has_dependencies = false;
	} 
	else {

		new Charitable_Billplz( __FILE__ );

	}	
}

add_action( 'plugins_loaded', 'charitable_billplz_load', 1 );

/*
 *  Remove Record created by this plugin
 */
register_uninstall_hook(__FILE__, 'charitable_billplz_uninstall');
function charitable_billplz_uninstall()
{
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'billplz_charitable_bill_id_%'");
}