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

if (!class_exists('Charitable_Gateway_Billplz')) {

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
                'help' => 'Enter your Billplz API Key. Get this key from Billplz >> Settings',
                'required' => true,
            );

            $settings['x_signature'] = array(
                'type' => 'text',
                'title' => __('X Signature Key', 'charitable-billplz'),
                'priority' => 8,
                'help' => 'Enter your Billplz X Signature Key. Get this key from Billplz >> Settings',
                'required' => true,
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

            $settings['description'] = array(
                'type' => 'text',
                'title' => __('Custom Description', 'charitable-billplz'),
                'priority' => 13,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );
            $settings['reference_1_label'] = array(
                'type' => 'text',
                'title' => __('Reference 1 Label', 'charitable-billplz'),
                'priority' => 13,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );
            $settings['reference_1'] = array(
                'type' => 'text',
                'title' => __('Reference 1', 'charitable-billplz'),
                'priority' => 13,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );
            $settings['reference_2_label'] = array(
                'type' => 'text',
                'title' => __('Reference 2 Label', 'charitable-billplz'),
                'priority' => 13,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );
            $settings['reference_2'] = array(
                'type' => 'text',
                'title' => __('Reference 2', 'charitable-billplz'),
                'priority' => 13,
                'help' => 'This is field is Optional. Leave blank if unsure'
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
                'send_bills' => trim($this->get_value('send_bills')),
                'description' => trim($this->get_value('description')),
                'reference_1_label' => trim($this->get_value('reference_1_label')),
                'reference_1' => trim($this->get_value('reference_1')),
                'reference_2_label' => trim($this->get_value('reference_2_label')),
                'reference_2' => trim($this->get_value('reference_2')),
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
        public static function redirect_to_processing($return, $donation_id)
        {

            $gateway = new Charitable_Gateway_Billplz();
            $donation = charitable_get_donation($donation_id);
            /*
             * Support for Advance Billplz for WP Charitable Plugin
             */
            $campaign_donations = $donation->get_campaign_donations();
            
            foreach ($campaign_donations as $key => $value) {
                if (!empty($value->campaign_id)) {
                    $post_id = $value->campaign_id;
                    $campaign_name = $value->campaign_name;
                    $post = get_post((int) $post_id);
                    $campaign = new Charitable_Campaign($post);
                    break;
                }
            }

            $donor = $donation->get_donor();
            $first_name = $donor->get_donor_meta('first_name');
            $last_name = $donor->get_donor_meta('last_name');
            $name = $first_name . ' ' . $last_name;
            $email = $donor->get_donor_meta('email');
            $mobile = $donor->get_donor_meta('phone');
            $amount = $donation->get_total_donation_amount(true);

            $keys = $gateway->get_keys();

            /**
             * If the admin has set Custom Description, use it
             */
            if (!empty($keys['description'])) {
                $raw_description = $keys['description'];
            } else {
                //$raw_description = sprintf(__('Donation %d', 'charitable-billplz'), $donation->ID);
                $raw_description = $campaign_name;
            }

            /*
             * The filter use it to get api key from campaign meta key
             * Example: empty($campaign->get('billplz_api_key')) ? $api_key : $campaign->get('billplz_api_key');
             */
            $api_key = apply_filters('billplz_for_wp_charitable_api_key', $keys['api_key'], $post, $campaign);
            $collection_id = apply_filters('billplz_for_wp_charitable_collection_id', $keys['collection_id'], $post, $campaign);
            $deliver = apply_filters('billplz_for_wp_charitable_deliver', $keys['send_bills'], $post, $campaign);
            $description = apply_filters('billplz_for_wp_charitable_description', $raw_description, $post, $campaign);
            $reference_1_label = apply_filters('billplz_for_wp_charitable_reference_1_label', $keys['reference_1_label'], $post, $campaign);
            $reference_1 = apply_filters('billplz_for_wp_charitable_reference_1', $keys['reference_1'], $post, $campaign);
            $reference_2_label = apply_filters('billplz_for_wp_charitable_reference_2_label', $keys['reference_2_label'], $post, $campaign);
            $reference_2 = apply_filters('billplz_for_wp_charitable_reference_2', $keys['reference_2'], $post, $campaign);
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
                ->setReference_2($reference_2)
                ->setReference_2_Label($reference_2_label)
                ->create_bill(true);

            $bill_url = $billplz->getURL();
            $bill_id = $billplz->getID();

            update_option('billplz_charitable_bill_id_' . $bill_id, $donation->ID, false);

            return array(
                'redirect' => $bill_url,
                'safe' => false,
            );
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
        /*
         * Add option to hide some element that not required by Billplz API
         */

        public static function add_billplz_fields($field)
        {
            $general_fields = array(
                'billplz_section_pages' => array(
                    'title' => __('Billplz for WP Charitable', 'charitable'),
                    'type' => 'heading',
                    'priority' => 50,
                ),
                'billplz_full_name' => array(
                    'title' => __('Replace First & Last Name with Full Name', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Use Malaysian Style Naming',
                    'priority' => 60,
                ),
                'billplz_rem_add' => array(
                    'title' => __('Remove Address 1 & 2 Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove Address Field on Payment',
                    'priority' => 70,
                ),
                'billplz_rem_city' => array(
                    'title' => __('Remove City Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove City Field on Payment',
                    'priority' => 80,
                ),
                'billplz_rem_state' => array(
                    'title' => __('Remove State Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove State Field on Payment',
                    'priority' => 90,
                ),
                'billplz_rem_postcode' => array(
                    'title' => __('Remove Postcode Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove Postcode Field on Payment',
                    'priority' => 100,
                ),
                'billplz_rem_country' => array(
                    'title' => __('Remove Country Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove Country Field on Payment',
                    'priority' => 110,
                ),
                'billplz_mak_phone' => array(
                    'title' => __('Phone Required', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Make Phone Fields Mandatory to be set',
                    'priority' => 120,
                ),
                'billplz_unr_email' => array(
                    'title' => __('Unrequire Email', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Make Email Fields Optional to be set. NOT RECOMMENDED',
                    'priority' => 120,
                ),
            );
            $field = array_merge($field, $general_fields);
            return $field;
        }

        public static function remove_unrequired_fields($fields)
        {

            $full_name = charitable_get_option('billplz_full_name', false);
            $address = charitable_get_option('billplz_rem_add', false);
            $city = charitable_get_option('billplz_rem_city', false);
            $state = charitable_get_option('billplz_rem_state', false);
            $postcode = charitable_get_option('billplz_rem_postcode', false);
            $country = charitable_get_option('billplz_rem_country', false);
            $phone = charitable_get_option('billplz_mak_phone', false);
            $email = charitable_get_option('billplz_unr_email', false);

            if ($full_name) {
                unset($fields['last_name']);
                $fields['first_name']['label'] = __('Name', 'charitable');
            }

            if ($address) {
                unset($fields['address']);
                unset($fields['address_2']);
            }

            if ($city) {
                unset($fields['city']);
            }
            if ($state) {
                unset($fields['state']);
            }
            if ($postcode) {
                unset($fields['postcode']);
            }
            if ($country) {
                unset($fields['country']);
            }

            if ($phone) {
                $fields['phone']['required'] = true;
            }

            if ($email) {
                $fields['email']['required'] = false;
            }

            return $fields;
        }
    }

} // End class_exists check
