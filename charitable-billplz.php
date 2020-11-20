<?php
/**
 * Plugin Name: Billplz for WP Charitable
 * Plugin URI: https://github.com/Billplz/billplz-for-wp-charitable
 * Description: Billplz. Fair payment platform.
 * Author: Billplz Sdn Bhd
 * Author URI: http://www.billplz.com/
 * Version: 3.2.0
 * Requires PHP: 7.0
 * License: GPLv3
 * Domain Path: /languages/
 * Text Domain: chbillplz
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

			require_once 'includes/activation.php';

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

function ch_billplz_db_migration() {
	require_once( 'includes/models/billplz-db.php' );

	$billplz_db = new ChBillplzDb();
  $billplz_db->migrate();
}

register_activation_hook(__FILE__, 'ch_billplz_db_migration');