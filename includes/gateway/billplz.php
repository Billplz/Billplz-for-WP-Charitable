<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Charitable_Gateway_Billplz')) {

    class Charitable_Gateway_Billplz extends Charitable_Gateway
    {
        const ID = 'billplz';

        public function __construct()
        {
            $this->name = apply_filters('charitable_gateway_billplz_name', __('Billplz', 'chbillplz'));

            $this->defaults = array(
                'label' => __('Billplz', 'chbillplz'),
            );

            $this->supports = array(
                '1.3.0',
            );

            /**
             * Needed for backwards compatibility with Charitable < 1.3
             */
            $this->credit_card_form = false;
        }

        public static function get_gateway_id()
        {
            return self::ID;
        }

        public function gateway_settings($settings)
        {
            if ('MYR' != charitable_get_option('currency', 'MYR')) {
                $settings['currency_notice'] = array(
                    'type' => 'notice',
                    'content' => $this->get_currency_notice(),
                    'priority' => 1,
                    'notice_type' => 'error',
                );
            }

            $settings['api_key'] = array(
                'type' => 'text',
                'title' => __('API Secret Key', 'chbillplz'),
                'priority' => 6,
                'help' => 'Enter your Billplz API Key.',
                'required' => true,
            );

            $settings['collection_id'] = array(
                'type' => 'text',
                'title' => __('Collection ID', 'chbillplz'),
                'priority' => 8,
                'help' => 'Enter your Billplz Collection ID.',
                'required' => true,
            );

            $settings['x_signature'] = array(
                'type' => 'text',
                'title' => __('X Signature Key', 'chbillplz'),
                'priority' => 10,
                'help' => 'Enter your Billplz X Signature Key.',
                'required' => true,
            );

            $settings['description'] = array(
                'type' => 'text',
                'title' => __('Custom Description', 'chbillplz'),
                'priority' => 12,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );

            $settings['reference_1_label'] = array(
                'type' => 'text',
                'title' => __('Reference 1 Label', 'chbillplz'),
                'priority' => 14,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );

            $settings['reference_1'] = array(
                'type' => 'text',
                'title' => __('Reference 1', 'chbillplz'),
                'priority' => 16,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );

            $settings['reference_2_label'] = array(
                'type' => 'text',
                'title' => __('Reference 2 Label', 'chbillplz'),
                'priority' => 18,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );

            $settings['reference_2'] = array(
                'type' => 'text',
                'title' => __('Reference 2', 'chbillplz'),
                'priority' => 20,
                'help' => 'This is field is Optional. Leave blank if unsure'
            );

            return $settings;
        }

        public function get_keys()
        {
            $keys = [
                'api_key' => trim($this->get_value('api_key')),
                'collection_id' => trim($this->get_value('collection_id')),
                'x_signature' => trim($this->get_value('x_signature')),
                'description' => trim($this->get_value('description')),
                'reference_1_label' => trim($this->get_value('reference_1_label')),
                'reference_1' => trim($this->get_value('reference_1')),
                'reference_2_label' => trim($this->get_value('reference_2_label')),
                'reference_2' => trim($this->get_value('reference_2')),
            ];

            return $keys;
        }

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
            
            $keys = $gateway->get_keys();

            if (!empty($keys['description'])) {
                $raw_description = $keys['description'];
            } else {
                $raw_description = $campaign_name;
            }

            $parameter = array(
              'collection_id' => apply_filters('billplz_for_wp_charitable_collection_id', $keys['collection_id'], $post, $campaign),
              'email' => trim($donor->get_donor_meta('email')),
              'mobile'=> trim($donor->get_donor_meta('phone')),
              'name' => trim($name),
              'amount' => strval($donation->get_total_donation_amount(true) * 100),
              'callback_url' => Charitable_Gateway_Billplz_Listener::get_listener_url(),
              'description' => mb_substr(apply_filters('billplz_for_wp_charitable_description', $raw_description, $post, $campaign),0,200)
            );

            $optional = array(
              'redirect_url' => $parameter['callback_url'],
              'reference_1_label' => mb_substr(apply_filters('billplz_for_wp_charitable_reference_1_label', $keys['reference_1_label'], $post, $campaign), 0, 20),
              'reference_1' => mb_substr(apply_filters('billplz_for_wp_charitable_reference_1', $keys['reference_1'], $post, $campaign), 0, 120),
              'reference_2_label' => mb_substr(apply_filters('billplz_for_wp_charitable_reference_2_label', $keys['reference_2_label'], $post, $campaign), 0, 20),
              'reference_2' => mb_substr(apply_filters('billplz_for_wp_charitable_reference_2', $keys['reference_2'], $post, $campaign), 0, 120)
            );

            $connect = BillplzChWPConnect::get_instance();
            $connect->set_api_key(trim(apply_filters('billplz_for_wp_charitable_api_key', $keys['api_key'], $post, $campaign)), charitable_get_option( 'test_mode' ));

            $billplz = BillplzChAPI::get_instance();
            $billplz->set_connect($connect);

            list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

            if ($rheader !== 200) {
              $this->log()->add(print_r($rbody,true));
              return false;
            }

            $bill_id = $rbody['id'];
            $row = array('bill_id' => $bill_id, 'donation_id' => $donation->ID);

            $billplz_model = new ChBillplz();
            $billplz_model->create($row);

            $donation->set_gateway_transaction_id($bill_id);

            return array(
                'redirect' => $rbody['url'],
                'safe' => false,
            );
        }

        public static function process_response(Charitable_Donation $donation)
        {
            return;
        }

        public function log() {
            if ( ! isset( $this->log ) ) {
                $this->log = new Charitable_Donation_Log( $this->ID );
            }

            return $this->log;
        }

        public function get_currency_notice()
        {
            ob_start();

            ?>        
            <?php
            printf(__('Billplz only accepts payments in Malaysian Ringgit. %sChange Now%s', 'chbillplz'), '<a href="#" class="button" data-change-currency-to-myr>', '</a>'
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

        public static function listener()
        {
            new Charitable_Gateway_Billplz_Listener;
        }
        /*
         * Add option to hide some element that not required by Billplz API
         */

        public static function add_billplz_fields($field)
        {
            $general_fields = array(
                'billplz_section_pages' => array(
                    'title' => __('Billplz Options', 'chbillplz'),
                    'type' => 'heading',
                    'priority' => 50,
                ),
                'billplz_full_name' => array(
                    'title' => __('Replace First & Last Name with Full Name', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Use Malaysian Style Naming',
                    'priority' => 60,
                ),
                'billplz_rem_add' => array(
                    'title' => __('Remove Address 1 & 2 Field', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Remove Address Field on Payment',
                    'priority' => 70,
                ),
                'billplz_rem_city' => array(
                    'title' => __('Remove City Field', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Remove City Field on Payment',
                    'priority' => 80,
                ),
                'billplz_rem_state' => array(
                    'title' => __('Remove State Field', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Remove State Field on Payment',
                    'priority' => 90,
                ),
                'billplz_rem_postcode' => array(
                    'title' => __('Remove Postcode Field', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Remove Postcode Field on Payment',
                    'priority' => 100,
                ),
                'billplz_rem_country' => array(
                    'title' => __('Remove Country Field', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Remove Country Field on Payment',
                    'priority' => 110,
                ),
                'billplz_mak_phone' => array(
                    'title' => __('Phone Required', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Make Phone Fields Mandatory to be set',
                    'priority' => 120,
                ),
                'billplz_unr_email' => array(
                    'title' => __('Unrequire Email', 'chbillplz'),
                    'type' => 'checkbox',
                    'help' => 'Make Email Fields Optional to be set. NOT RECOMMENDED',
                    'priority' => 130,
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
                $fields['first_name']['label'] = __('Name', 'chbillplz');
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
}
