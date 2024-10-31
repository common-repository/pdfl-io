<?php
/*
Plugin Name: pdfl.io
Description: Get PDFs from pdfl.io
Version:     1.0.5
Author:      tripleNERDscre
Author URI:  https://triplenerdscore.net
Text Domain: pdflio
*/

// No direct access.
defined( 'ABSPATH' ) or die;
define( 'PDFLIO_VER', '1.0.0' );
define( 'PDFLIO_CACHE_PATH', __DIR__ . '/cache'  );

if ( ! class_exists( 'PDFLIO_Main' ) ) {
	class PDFLIO_Main {
		public static function get_instance() {
			if ( self::$instance == null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private static $instance = null;

		private function __clone() { }

		private function __wakeup() { }

		private function __construct() {
			// Properties
			$this->options = null;

			// Actions
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_plugin_menu' ) );
			add_action( 'admin_post_pdflio_process', array( $this, 'process_request' ) );
			add_action( 'admin_post_nopriv_pdflio_process', array( $this, 'process_request' ) );

			// Shortcode
			add_shortcode( 'pdflio', array( $this, 'output_shortcode' ) );
		}

		public function init() {
			load_plugin_textdomain( 'pdflio', false, dirname( plugin_basename( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'languages' );
		}

		public function register_plugin_settings() {
			register_setting( 'pdflio_optsgroup', 'pdflio_options' );
		}

		public function add_plugin_menu() {
			add_menu_page(
				__( 'pdfl.io', 'pdflio' ),
				__( 'pdfl.io', 'pdflio' ),
				'manage_options',
				'pdflio',
				array( $this, 'output_menu_page' )
			);
			add_submenu_page(
				null,
				__( 'pdfl.io', 'pdflio' ),
				__( 'pdfl.io', 'pdflio' ),
				'manage_options',
				'pdflio-help',
				array( $this, 'output_help_page' )
			);
		}

		public function output_menu_page() {
			require( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'options.php' );
		}

		public function output_help_page() {
			require( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'help.php' );
		}

		public function output_shortcode( $atts ) {
			extract( shortcode_atts( array(
				'filename'           => 'file.pdf',
				'text'               => __( 'Download', 'pdflio' ),
				'url'                => '',
				'format'             => '',
				'no_background'      => '',
				'greyscale'          => '',
				'top_view_only'      => '',
				'disable_javascript' => '',
				'disable_images'     => '',
				'just_wait'          => '',
				'delay'              => ''
			), $atts, 'pdflio' ) );

			$api_key = trim( $this->get_option( 'api_key' ) );
			if ( $api_key == '' ) return __( 'API key has not been set up', 'pdflio' );

			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				global $wp;
				$url = home_url( $wp->request );
			}

			$fields = array(
				'url'      => 'url=' . urlencode( $url ),
				'filename' => 'filename=' . $filename,
			);

			if ( $format != '' )             array_push( $fields, 'format=' . $format );
			if ( $no_background != '' )      array_push( $fields, 'no_background=' . $no_background );
			if ( $greyscale != '' )          array_push( $fields, 'greyscale=' . $greyscale );
			if ( $top_view_only != '' )      array_push( $fields, 'top_view_only=' . $top_view_only );
			if ( $disable_javascript != '' ) array_push( $fields, 'disable_javascript=' . $disable_javascript );
			if ( $disable_images != '' )     array_push( $fields, 'disable_images=' . $disable_images );
			if ( $just_wait != '' )          array_push( $fields, 'just_wait=' . $just_wait );
			if ( $delay != '' )              array_push( $fields, 'delay=' . $delay  );

			return '<a href="' . admin_url( 'admin-post.php?action=pdflio_process&' . implode( '&', $fields ) ) . '">' . $text . '</a>';
		}

		public function process_request() {
			require_once( __DIR__ . '/lib/FilesystemException.php' );
			require_once( __DIR__ . '/lib/Filesystem.php' );
			require_once( __DIR__ . '/classes/class.pdflio.php' );

			$api_key = trim( $this->get_option( 'api_key' ) );
			if ( $api_key == '' ) wp_die( __( 'API key has not been set up', 'pdflio' ) );

			$instance = new Pdflio( $api_key );
			$instance->get();
			die;
		}

		private function get_option( $option_name, $default = '' ) {
			if ( is_null( $this->options ) ) $this->options = ( array ) get_option( 'pdflio_options', array() );
			if ( isset( $this->options[$option_name] ) ) return $this->options[$option_name];
			return $default;
		}
	}
}

PDFLIO_Main::get_instance();