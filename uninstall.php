<?php 

if (!defined('WP_UNINSTALL_PLUGIN')) exit;

require_once( 'includes/models/billplz-db.php' );

$billplz_db = new ChBillplzDb();
$billplz_db->rollback();

// Legacy. To be removed in future release
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'billplz_charitable_bill_id_%'");