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
		require_once FAST_CHECKOUT_PATH . 'widgets/class-product-card-widget.php';
		require_once FAST_CHECKOUT_PATH . 'widgets/class-cart-summary-widget.php';
	}

	public function register_widgets() {
		$this->include_widgets_files();

		$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;

		$widgets_manager->register( new \FastCheckout\Product_Card_Widget() );
		$widgets_manager->register( new \FastCheckout\Cart_Summary_Widget() );
	}

	public function widget_styles() {
		wp_register_style('fast-checkout-card',FAST_CHECKOUT_URL . 'assets/card.css',[],FAST_CHECKOUT_VERSION);
		wp_register_style('fast-cart-summary',FAST_CHECKOUT_URL . 'assets/cart-summary.css',[],FAST_CHECKOUT_VERSION);
	}

	public function widget_scripts() {
		wp_register_script('fast-checkout-card',FAST_CHECKOUT_URL . 'assets/card.js',[],FAST_CHECKOUT_VERSION,true
		);
		wp_register_script('fast-cart-summary',FAST_CHECKOUT_URL . 'assets/cart-summary.js',[],FAST_CHECKOUT_VERSION,true
		);
	}

	public function __construct() {
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ] );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
	}
}
