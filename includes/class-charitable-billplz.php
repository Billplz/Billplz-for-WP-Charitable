<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Charitable_Billplz' ) ) :

	class Charitable_Billplz {

		private static $instance = null;
		private $plugin_file;
		private $directory_path;
		private $directory_url;
		private $registry;

		public function __construct( $plugin_file ) {
			$this->plugin_file      = $plugin_file;
			$this->directory_path   = plugin_dir_path( $plugin_file );
			$this->directory_url    = plugin_dir_url( $plugin_file );

			add_action( 'charitable_start', array( $this, 'start' ), 1 );
		}

		public static function get_instance() {
			return self::$instance;
		}

		public function start() {
			if ( $this->started() ) {
				return;
			}

			self::$instance = $this;

			$this->load_dependencies();

			$this->attach_hooks_and_filters();

			// Hook in here to do something when the plugin is first loaded.
			do_action( 'charitable_billplz_start', $this );
		}

		private function load_dependencies() {
      require_once( $this->get_path( 'models' ) . 'billplz.php' );

      require_once( $this->get_path( 'gateway' ) . 'billplz.php' );
      require_once( $this->get_path( 'gateway' ) . 'hooks.php' );
      require_once( $this->get_path( 'gateway' ) . 'listener.php' );

      require_once( $this->get_path( 'helpers' ) . 'billplz_api.php' );
      require_once( $this->get_path( 'helpers' ) . 'billplz_wpconnect.php' );
		}

		private function attach_hooks_and_filters() {
			add_filter( 'plugin_action_links_' . plugin_basename( $this->get_path() ), array( $this, 'add_plugin_action_links' ) );
			add_filter( 'charitable_payment_gateways', array( $this, 'register_gateway' ) );
		}

		public function add_plugin_action_links( $links ) {
			$link = add_query_arg( array(
				'page'  => 'charitable-settings',
				'tab'   => 'gateways',
			), admin_url( 'admin.php' ) );

			$link_text = __( 'Settings', 'chbillplz' );

			if ( charitable_get_helper( 'gateways' )->is_active_gateway( 'billplz' ) ) {

				$link = add_query_arg( array(
					'group' => 'gateways_billplz',
				), $link );

			}

			$links[] = "<a href=\"$link\">$link_text</a>";

			return $links;
		}

		public function register_gateway( $gateways ) {
			$gateways['billplz'] = 'Charitable_Gateway_Billplz';
			return $gateways;
		}

		public function is_start() {
			return current_filter() == 'charitable_billplz_start';
		}

		public function started() {
			return did_action( 'charitable_billplz_start' ) || current_filter() == 'charitable_billplz_start';
		}

		public function get_version() {
			return self::VERSION;
		}

		public function get_path( $type = '', $absolute_path = true ) {
			$base = $absolute_path ? $this->directory_path : $this->directory_url;

			switch ( $type ) {
				case 'models' :
				  $path = $base . 'includes/models/';
				  break;

				case 'gateway' :
					$path = $base . 'includes/gateway/';
					break;

				case 'helpers' :
					$path = $base . 'includes/helpers/';
				  break;

				case 'directory' :
					$path = $base;
					break;

				default :
					$path = $this->plugin_file;
			}

			return $path;
		}

		public function register_object( $object ) {
			if ( ! is_object( $object ) ) {
				return;
			}

			$class = get_class( $object );

			$this->registry[ $class ] = $object;
		}

		public function get_object( $class ) {
			return isset( $this->registry[ $class ] ) ? $this->registry[ $class ] : false;
		}

		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'chbillplz' ), '1.0.0' );
		}

		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'chbillplz' ), '1.0.0' );
		}
	}

endif;
