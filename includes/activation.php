<?php

if (!defined('ABSPATH')) exit;

class Charitable_Extension_Activation
{

    public $plugin_name, $plugin_path, $plugin_file, $has_charitable, $charitable_base;

    public function __construct($plugin_path, $plugin_file)
    {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $plugins = get_plugins();

        $plugin_path = array_filter(explode('/', $plugin_path));
        $this->plugin_path = end($plugin_path);
        $this->plugin_file = $plugin_file;

        if (isset($plugins[$this->plugin_path . '/' . $this->plugin_file]['Name'])) {
            $this->plugin_name = str_replace('Charitable - ', '', $plugins[$this->plugin_path . '/' . $this->plugin_file]['Name']);
        } else {
            $this->plugin_name = __('Billplz for WP Charitable', 'chbillplz');
        }

        foreach ($plugins as $plugin_path => $plugin) {
            if ($plugin['Name'] == 'Charitable') {
                $this->has_charitable = true;
                $this->charitable_base = $plugin_path;
                break;
            }
        }
    }

    public function run()
    {
        add_action('admin_notices', array($this, 'missing_charitable_notice'));
    }

    public function missing_charitable_notice()
    {
        if ($this->has_charitable) {
            $url = esc_url(wp_nonce_url(admin_url('plugins.php?action=activate&plugin=' . $this->charitable_base), 'activate-plugin_' . $this->charitable_base));
            $link = '<a href="' . $url . '">' . __('activate it', 'chbillplz') . '</a>';
        } else {
            $url = esc_url(wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=charitable'), 'install-plugin_charitable'));
            $link = '<a href="' . $url . '">' . __('install it', 'chbillplz') . '</a>';
        }

        echo '<div class="error"><p>' . $this->plugin_name . sprintf(__(' requires Charitable! Please %s to continue!', 'chbillplz'), $link) . '</p></div>';
    }
}
