<?php

add_filter('charitable_process_donation_billplz', array('Charitable_Gateway_Billplz', 'redirect_to_processing'), 10, 3);

add_filter('charitable_donation_form_user_fields', array('Charitable_Gateway_Billplz', 'remove_unrequired_fields'));

add_action('charitable_donation_receipt_page', array('Charitable_Gateway_Billplz', 'process_response'));

add_action('wp_ajax_charitable_change_currency_to_myr', array('Charitable_Gateway_Billplz', 'change_currency_to_myr'));

add_action('init', array('Charitable_Gateway_Billplz', 'listener'));

add_filter('charitable_settings_tab_fields_general', array('Charitable_Gateway_Billplz', 'add_billplz_fields'), 6);
