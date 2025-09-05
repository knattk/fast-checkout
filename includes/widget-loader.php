<?php

namespace FastCheckout;

if ( ! defined( 'ABSPATH' ) ) exit;

class Widget_Loader {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	private function include_widgets_files() {
		require_once FAST_CHECKOUT_PATH . 'widgets/product-card.php';
		require_once FAST_CHECKOUT_PATH . 'widgets/cart-summary.php';
		require_once FAST_CHECKOUT_PATH . 'widgets/checkout-form.php';
	}


	public function register_widgets() {
		$this->include_widgets_files();

		$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;

		$widgets_manager->register( new \FastCheckout\Product_Card_Widget() );
		$widgets_manager->register( new \FastCheckout\Cart_Summary_Widget() );
		$widgets_manager->register( new \FastCheckout\Checkout_Form_Widget() );
	}

	public function localize_checkout_config() {
		// Only if our JS handle is registered
		if ( ! wp_script_is( 'fast-cart-checkout-form', 'registered' ) ) {
			return;
		}

	
		wp_localize_script( 'fast-cart-checkout-form', 'FC_Config', [
			
			'rest_base' => esc_url_raw( rest_url( 'fc/v1/' ) ),
			'wp_nonce'  => wp_create_nonce( 'wp_rest' ),   // for logged-in (X-WP-Nonce)
			'csrf'      => wp_create_nonce( 'fc_public' ), // for guests
			'endpoints' => [
				'otp_request'  => 'otp/request',
				'otp_verify'   => 'otp/verify',
				'order_create' => 'order/create',
				],
			]

		);


		// (
		// 	'fast-checkout-otp',
		// 	'ajax_otp_handler_script',
		// 	[
		// 		'ajax_url'      => admin_url('admin-ajax.php'),
		// 		'nonce'         => wp_create_nonce('otp_verify_nonce_action'),
		// 		'action_send'   => 'otp_send',   // ชื่อ action ฝั่ง PHP
		// 		'action_verify' => 'otp_verify', // ชื่อ action ฝั่ง PHP
		// 	]
		// );
	}
	public function lib_dependencies_script() {

		// html2canvas
		wp_register_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], '1.4.1', true);

		// Thailand.js 
		wp_register_script('jquery-3.2.1', 'https://code.jquery.com/jquery-3.2.1.min.js', [], '3.2.1', true);
		wp_register_script('jquery-thailand', 'https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js', [], '1.0.0', true);
		wp_register_script('jquery-thailand-typeahead', 'https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js', [], '1.0.0', true);
		wp_register_script('jquery-thailand-main', 'https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js', [], '1.0.0', true);
	
	}
	
	public function lib_dependencies_style() {

		// Thailand.js CSS
		wp_register_style('jquery-thailand-css', 'https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css');
	
	}
	

	public function widget_styles() {
		wp_register_style('fast-checkout-card',FAST_CHECKOUT_URL . 'assets/css/product-card.css',[],FAST_CHECKOUT_VERSION);
		wp_register_style('fast-cart-summary',FAST_CHECKOUT_URL . 'assets/css/cart-summary.css',[],FAST_CHECKOUT_VERSION);
		wp_register_style('fast-cart-checkout-form',FAST_CHECKOUT_URL . 'assets/css/checkout-form.css',[],FAST_CHECKOUT_VERSION);
		wp_register_style('fast-checkout-popup',FAST_CHECKOUT_URL . 'assets/css/popup.css',[],FAST_CHECKOUT_VERSION);
	}

	public function widget_scripts() {

		// lib dependencies
		
		wp_register_script('fast-checkout-card',FAST_CHECKOUT_URL . 'assets/js/product-card.js',[],FAST_CHECKOUT_VERSION,true);
		wp_register_script('fast-cart-summary',FAST_CHECKOUT_URL . 'assets/js/cart-summary.js',[],FAST_CHECKOUT_VERSION,true);
		wp_register_script('fast-checkout-popup',FAST_CHECKOUT_URL . 'assets/js/popup-controller.js',['html2canvas'],FAST_CHECKOUT_VERSION,true);
		wp_register_script('fast-checkout-otp',FAST_CHECKOUT_URL . 'assets/js/otp-controller.js',['fast-checkout-popup'],FAST_CHECKOUT_VERSION,true);
		wp_register_script('fast-cart-checkout-form',FAST_CHECKOUT_URL . 'assets/js/checkout-form.js',['fast-checkout-popup'],FAST_CHECKOUT_VERSION,true);
		

		// wp_register_script('fast-cart-checkout-form_order-limit',FAST_CHECKOUT_URL . 'assets/js/order-limit.js',[],FAST_CHECKOUT_VERSION,true);


		// Make REST config available to checkout-form.js
		$this->localize_checkout_config();
	}

	

	public function __construct() {
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'lib_dependencies_script' ] );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'lib_dependencies_style' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ] );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		
	}

}



