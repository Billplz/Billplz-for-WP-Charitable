<?php
/**
 * Charitable Billplz Gateway Hooks.
 *
 * Action/filter hooks used for handling payments through the Billplz gateway.
 *
 * @package     Charitable Billplz/Hooks/Gateway
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2017, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Process the donation.
 *
 * @see     Charitable_Gateway_Billplz::process_donation()
 */
add_filter('charitable_process_donation_billplz', array('Charitable_Gateway_Billplz', 'redirect_to_processing'), 10, 3);

/**
 * Remove the options according to the user settings
 */
add_filter('charitable_donation_form_user_fields', array('Charitable_Gateway_Billplz', 'remove_unrequired_fields'));


/**
 * Render the Billplz donation processing page content.
 *
 * This is the page that users are redirected to after filling out the donation form.
 * It automatically redirects them to Billplz's website.
 *
 * @see Charitable_Gateway_Billplz::process_donation()
 */
//add_filter('charitable_processing_donation_billplz', array('Charitable_Gateway_Billplz', 'process_donation'), 10, 2);

/**
 * Check the response from Billplz after the donor has completed payment.
 *
 * @see Charitable_Gateway_Billplz::process_response()
 */
add_action('charitable_donation_receipt_page', array('Charitable_Gateway_Billplz', 'process_response'));

/**
 * Change the currency to MYR.
 *
 * @see Charitable_Gateway_Billplz::change_currency_to_myr()
 */
add_action('wp_ajax_charitable_change_currency_to_myr', array('Charitable_Gateway_Billplz', 'change_currency_to_myr'));

/**
 * IPN listener.
 *
 * @see     charitable_ipn_listener()
 */
add_action('init', array('Charitable_Gateway_Billplz', 'ipn_listener'));

/**
 * Add settings to the General tab.
 *
 */
add_filter('charitable_settings_tab_fields_general', array('Charitable_Gateway_Billplz', 'add_billplz_fields'), 6);
