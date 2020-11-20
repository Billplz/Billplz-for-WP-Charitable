<?php
if (!class_exists('Charitable_Gateway_Billplz_Listener')) {

    class Charitable_Gateway_Billplz_Listener
    {

        const QUERY_VAR = 'billplz_charitable_call';
        const LISTENER_PASSPHRASE = 'billplz_listener_passphrase';

        function __construct()
        {
            $this->listen();
        }

        public static function get_listener_url()
        {
            $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
            if (!$passphrase) {
                $passphrase = md5(site_url() . time());
                update_option(self::LISTENER_PASSPHRASE, $passphrase);
            }
            return add_query_arg(self::QUERY_VAR, $passphrase, site_url('/'));
        }

        private function listen()
        {
            if (!isset($_GET[self::QUERY_VAR]))
                return;
            $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
            if (!$passphrase) {
                return;
            }
            if ($_GET[self::QUERY_VAR] != $passphrase) {
                return;
            }

            $this->update_order_status();
            wp_die('Successful Callback');
        }

        private function update_order_status()
        {
            $gateway = new Charitable_Gateway_Billplz();
            $keys = $gateway->get_keys();

            /*
             * Support for Advance Billplz for WP Charitable Plugin
             */
            $bill_id = htmlspecialchars(isset($_GET['billplz']['id']) ? $_GET['billplz']['id'] : $_POST['id']);

            $billplz_model = new ChBillplz();
            $payment_row = $billplz_model->get_payment_by_bill_id($bill_id);
            if (!$payment_row) {
                exit('No record found');
            }

            $donation_id = $payment_row->donation_id;
            
            $donation = charitable_get_donation((int) $donation_id);

            $campaign_donations = $donation->get_campaign_donations();
            foreach ($campaign_donations as $key => $value) {
                if (!empty($value->campaign_id)) {
                    $post_id = $value->campaign_id;
                    $post = get_post((int) $post_id);
                    $campaign = new Charitable_Campaign($post);
                    break;
                }
            }

            $x_signature = apply_filters('billplz_for_wp_charitable_x_signature', $keys['x_signature'], $post, $campaign);

            $data = BillplzChWPConnect::getXSignature($x_signature);

            $is_sandbox = charitable_get_option( 'test_mode' ) ? 'yes' : 'no';
            $transaction_id = isset($data['transaction_id']) ? $data['transaction_id'] : '';

            if ($data['paid']) {
                /* If the donation had already been marked as complete, stop here. */
                if ('charitable-completed' != get_post_status($donation_id)) {
                    $message = 'Is Sandbox: ' . $is_sandbox;
                    $message .= '<br>Bill ID: ' . $data['id'];
                    $message .= '<br>Transaction ID: ' . $transaction_id;
                    $message .= '<br>Donation ID: ' . $donation_id;
                    $donation->update_donation_log($message);
                    $donation->update_status('charitable-completed');
                }
            } elseif ('charitable-completed' != get_post_status($donation_id)) {
                /*
                 * Prevent completed order marked as failed for unpaid bills
                 */
                $message = sprintf('%s: %s', __('The donation has failed with the following state', 'charitable'), $moreData['state']);
                $donation->update_donation_log($message);
                $donation->update_status('charitable-failed');
            }

            if ($data['type'] == 'redirect') {
                if ($data['paid']) {
                    $return_url = charitable_get_permalink('donation_receipt_page', array('donation_id' => $donation->ID));
                    header('Location: ' . $return_url);
                } else {
                    $cancel_url = charitable_get_permalink('donation_cancel_page', array('donation_id' => $donation->ID));

                    if (!$cancel_url) {
                        $cancel_url = esc_url(add_query_arg(array(
                            'donation_id' => $donation->ID,
                            'cancel' => true,
                                ), wp_get_referer()));
                    }
                    header('Location: ' . $cancel_url);
                }
                exit;
            } else {
                wp_die('OK');
            }
        }
    }
}