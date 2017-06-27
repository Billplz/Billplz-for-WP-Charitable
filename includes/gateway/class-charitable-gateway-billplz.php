<?php
/**
 * Billplz Gateway class
 *
 * @package     Charitable/Classes/Charitable_Gateway_Billplz
 * @copyright   Copyright (c) 2017, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Charitable_Gateway_Billplz')) :

    /**
     * Billplz Gateway
     *
     * @since       1.0.0
     */
    class Charitable_Gateway_Billplz extends Charitable_Gateway
    {

        /**
         * @var     string
         */
        const ID = 'billplz';

        /**
         * Instantiate the gateway class, defining its key values.
         *
         * @access  public
         * @since   1.0.0
         */
        public function __construct()
        {
            $this->name = apply_filters('charitable_gateway_billplz_name', __('Billplz', 'charitable-billplz'));

            $this->defaults = array(
                'label' => __('Billplz', 'charitable-billplz'),
            );

            $this->supports = array(
                '1.3.0',
            );

            /**
             * Needed for backwards compatibility with Charitable < 1.3
             */
            $this->credit_card_form = false;
        }

        /**
         * Returns the current gateway's ID.
         *
         * @return  string
         * @access  public
         * @static
         * @since   1.0.3
         */
        public static function get_gateway_id()
        {
            return self::ID;
        }

        /**
         * Register gateway settings.
         *
         * @param   array $settings
         * @return  array
         * @access  public
         * @since   1.0.0
         */
        public function gateway_settings($settings)
        {
            if ('MYR' != charitable_get_option('currency', 'AUD')) {
                $settings['currency_notice'] = array(
                    'type' => 'notice',
                    'content' => $this->get_currency_notice(),
                    'priority' => 1,
                    'notice_type' => 'error',
                );
            }

            $settings['api_key'] = array(
                'type' => 'text',
                'title' => __('API Secret Key', 'charitable-billplz'),
                'priority' => 6,
                'help' => 'Enter your Billplz API Key. Get this key from Billplz >> Settings'
            );

            $settings['x_signature'] = array(
                'type' => 'text',
                'title' => __('X Signature Key', 'charitable-billplz'),
                'priority' => 8,
                'help' => 'Enter your Billplz X Signature Key. Get this key from Billplz >> Settings'
            );

            $settings['collection_id'] = array(
                'type' => 'text',
                'title' => __('Collection ID', 'charitable-billplz'),
                'priority' => 10,
                'help' => 'Enter your Billplz Collection ID. This is field is Optional'
            );

            $settings['send_bills'] = array(
                'type' => 'radio',
                'title' => __('Send Bills to Payer', 'charitable-billplz'),
                'priority' => 12,
                'options' => array(
                    '0' => __('Do not Send', 'charitable-billplz'),
                    '1' => __('Send Email (FREE)', 'charitable-billplz'),
                    '2' => __('Send SMS (RM0.15)', 'charitable-billplz'),
                    '3' => __('Send Both (RM0.15)', 'charitable-billplz'),
                ),
                'default' => '0',
                'help' => 'We recommend "Do not Send" option',
            );

            return $settings;
        }

        /**
         * Return the keys to use.
         *
         * @return  string[]
         * @access  public
         * @since   1.0.0
         */
        public function get_keys()
        {
            $keys = [
                'api_key' => trim($this->get_value('api_key')),
                'x_signature' => trim($this->get_value('x_signature')),
                'collection_id' => trim($this->get_value('collection_id')),
                'send_bills' => trim($this->get_value('send_bills'))
            ];

            return $keys;
        }

        /**
         * Process the donation with Billplz.
         *
         * @param   Charitable_Donation $donation
         * @return  void
         * @access  public
         * @static
         * @since   1.0.0
         */
        public static function process_donation($content, Charitable_Donation $donation)
        {
            $gateway = new Charitable_Gateway_Billplz();
            $donor = $donation->get_donor();
            $first_name = $donor->get_donor_meta('first_name');
            $last_name = $donor->get_donor_meta('last_name');
            $name = $first_name . ' ' . $last_name;
            $email = $donor->get_donor_meta('email');
            $mobile = $donor->get_donor_meta('phone');
            $reference_1 = $donation->ID;
            $reference_1_label = 'ID';
            //$reference_2 = $donation->get_donation_key();
            //$reference_2_label = 'KEY';
            $amount = $donation->get_total_donation_amount(true);
            $description = sprintf(__('Donation %d', 'charitable-billplz'), $donation->ID);
            $keys = $gateway->get_keys();
            $api_key = $keys['api_key'];
            $collection_id = $keys['collection_id'];
            $deliver = $keys['send_bills'];

            $ipn_url = Charitable_Gateway_Billplz_IPN_Listener::get_listener_url();

            $billplz = new Billplz($api_key);
            $billplz
                ->setAmount($amount)
                ->setCollection($collection_id)
                ->setDeliver($deliver)
                ->setDescription($description)
                ->setEmail($email)
                ->setMobile($mobile)
                ->setName($name)
                ->setPassbackURL($ipn_url, $ipn_url)
                ->setReference_1($reference_1)
                ->setReference_1_Label($reference_1_label)
                //->setReference_2($reference_2)
                //->setReference_2_Label($reference_2_label)
                ->create_bill(true);

            $bill_url = $billplz->getURL();
            ob_start();
            
            $content = ob_get_clean();
            
            if (!headers_sent()) {
                wp_redirect(esc_url_raw($bill_url));
                return $content;
            } 
            
            $stroutput = "Redirecting to Billplz... If you are not redirected, please click <a href=" . '"' . $bill_url . '"' . " target='_self'>Here</a><br />"
                        . "<script>location.href = '" . $bill_url . "'</script>";

            echo $stroutput;

            return $content;
        }

        /**
         *
         * @param   Charitable_Donation $donation
         * @return  void
         * @access  public
         * @static
         * @since   1.0.0
         */
        public static function process_response(Charitable_Donation $donation)
        {
            return;
        }

        /**
         * Update the donation's log. 
         *
         * @return  void
         * @access  public
         * @static 
         * @since   1.1.0		 
         */
        public static function update_donation_log($donation, $message)
        {
            if (version_compare(charitable()->get_version(), '1.4.0', '<')) {
                return Charitable_Donation::update_donation_log($donation->ID, $message);
            }

            return $donation->update_donation_log($message);
        }

        /**
         * Return the HTML for the currency notice.
         *
         * @return  string
         * @access  public
         * @since   1.0.0
         */
        public function get_currency_notice()
        {
            ob_start();

            ?>        
            <?php
            printf(__('Billplz only accepts payments in Malaysian Ringgit. %sChange Now%s', 'charitable-billplz'), '<a href="#" class="button" data-change-currency-to-myr>', '</a>'
            )

            ?>
            <script>
                        (function ($) {
                            $('[data-change-currency-to-myr]').on('click', function () {
                                var $this = $(this);

                                $.ajax({
                                    type: "POST",
                                    data: {
                                        action: 'charitable_change_currency_to_myr',
                                        _nonce: "<?php echo wp_create_nonce('billplz_currency_change') ?>"
                                    },
                                    url: ajaxurl,
                                    success: function (response) {
                                        console.log(response);

                                        if (response.success) {
                                            $this.parents('.notice').first().slideUp();
                                        }
                                    },
                                    error: function (response) {
                                        console.log(response);
                                    }
                                });
                            })
                        })(jQuery);
            </script>
            <?php
            return ob_get_clean();
        }

        /**
         * Change the currency to MYR.
         *
         * @return  void
         * @access  public
         * @static
         * @since   1.0.0
         */
        public static function change_currency_to_myr()
        {
            if (!wp_verify_nonce($_REQUEST['_nonce'], 'billplz_currency_change')) {
                wp_send_json_error();
            }

            $settings = get_option('charitable_settings');
            $settings['currency'] = 'MYR';
            $updated = update_option('charitable_settings', $settings);

            wp_send_json(array('success' => $updated));
            wp_die();
        }
        /*
         * Listen to Billplz Callback & Return
         */

        public static function ipn_listener()
        {
            new Charitable_Gateway_Billplz_IPN_Listener;
        }
    }

    

    

    

    

endif; // End class_exists check
