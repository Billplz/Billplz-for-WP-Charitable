<?php

class ChBillplz
{

    var $payments;

    function __construct()
    {
        global $wpdb;
        $this->payments = $wpdb->prefix . 'ch_billplz_payments';
    }
    
    function create($values = array())
    {
        if (!isset($values['bill_id']) || !isset($values['donation_id'])){
            return false;
        }

        global $wpdb;

        $query_results = $wpdb->insert($this->payments, $values);

        return $wpdb->insert_id;
    }
   
    function get_payment_by_bill_id($bill_id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . $this->payments .' WHERE bill_id=%s', $bill_id));
    }
}
