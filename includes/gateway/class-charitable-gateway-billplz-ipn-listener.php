<?php
if (!class_exists('Charitable_Gateway_Billplz_IPN_Listener')) {

    class Charitable_Gateway_Billplz_IPN_Listener
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
            $api_key = $keys['api_key'];
            $x_sign = $keys['x_signature'];

            /*
             * Support for Advance Billplz for WP Charitable Plugin
             */
            $bill_id = htmlspecialchars(isset($_GET['billplz']['id']) ? $_GET['billplz']['id'] : $_POST['id']);
            $donation_id = get_option('billplz_charitable_bill_id_' . $bill_id, false);
            if (!$donation_id) {
                exit;
            }
            
            $donation = charitable_get_donation((int) $donation_id);
            //$donation_key = $moreData['reference_2'];

            $campaign_donations = $donation->get_campaign_donations();
            foreach ($campaign_donations as $key => $value) {
                if (!empty($value->campaign_id)) {
                    $post_id = $value->campaign_id;
                    break;
                }
            }
            if (class_exists('AdvanceBFC')) {
                $post = get_post((int) $post_id);
                $campaign = new Charitable_Campaign($post);
                $api_key = empty($campaign->get('billplz_api_key')) ? $api_key : $campaign->get('billplz_api_key');
                $x_sign = empty($campaign->get('billplz_x_signature')) ? $x_sign : $campaign->get('billplz_x_signature');
            }

            if (isset($_GET['billplz']['id'])) {
                $data = Billplz::getRedirectData($x_sign);
            } else {
                $data = Billplz::getCallbackData($x_sign);
                sleep(10);
            }

            $billplz = new Billplz($api_key);
            $moreData = $billplz->check_bill($data['id']);

            if ($data['paid']) {

                /* If the donation had already been marked as complete, stop here. */
                if ('charitable-completed' != get_post_status($donation_id)) {
                    $paid_time = $billplz->get_bill_paid_time($data['id']);
                    $message = sprintf('%s: %s', __('Billplz Bill ID', 'charitable'), $data['id']);
                    $message .= '<br>Bill URL: ' . $moreData['url'];
                    $message .= '<br>Payment Date: ' . gmdate('d-m-Y H:i:s', $paid_time);
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

            if (isset($_GET['billplz']['id'])) {
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