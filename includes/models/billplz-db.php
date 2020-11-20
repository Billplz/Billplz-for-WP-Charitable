<?php

class ChBillplzDb
{
   
    var $payments;

    const VERSION = '1.0';
    const DB_OPT_NAME = 'ch_billplz_db_version';

    function __construct()
    {
        global $wpdb;
        $this->payments = $wpdb->prefix . 'ch_billplz_payments';
    }

    function migrate()
    {
        global $wpdb;
        
        $db_version = self::VERSION; //$db_version is the version of the database we're moving to
        $db_opt_name = self::DB_OPT_NAME;
        
        $old_db_version = get_option($db_opt_name);

        if ($db_version != $old_db_version) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)) {
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                }
                if (!empty($wpdb->collate)) {
                    $charset_collate .= " COLLATE $wpdb->collate";
                }
            }

            /* Create/Upgrade Payments Table */
            $sql = "CREATE TABLE {$this->payments} (
                    id bigint(20) NOT NULL auto_increment,
                    bill_id varchar(100) NOT NULL,
                    donation_id bigint(20) NOT NULL,
                    PRIMARY KEY  (id),
                    UNIQUE KEY ch_billplz_id_slug (bill_id)
                  ) {$charset_collate};";

            dbDelta($sql);

            /***** SAVE DB VERSION *****/
            update_option($db_opt_name, $db_version);
        }
    }

    function rollback()
    {
        global $wpdb;

        $db_opt_name = self::DB_OPT_NAME;
        
        $old_db_version = get_option($db_opt_name);

        if ($old_db_version){

            $sql = "DROP TABLE {$this->payments};";
            $wpdb->query($sql);

            delete_option($db_opt_name);
            error_log('gila');
        }

    }
}
