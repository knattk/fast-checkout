<?php
/**
 * Plugin Name: Fast Checkout
 * Description: A custom Elementor widget plugin for WooCommerce API integration.
 * Version: 1.0.3
 * Author: Khwaaan
 * Text Domain: fast-checkout
 */

namespace FastCheckout;

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FAST_CHECKOUT_VERSION', '1.0.3' );
define( 'FAST_CHECKOUT_PATH', plugin_dir_path( __FILE__ ) );
define( 'FAST_CHECKOUT_URL', plugin_dir_url( __FILE__ ) );

// Load after Elementor is ready
add_action('plugins_loaded', function () {
	if ( ! did_action('elementor/loaded') ) {
		add_action('admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Fast Checkout</strong> requires Elementor to be installed and activated.</p></div>';
		});
		return;
	}

	// Load Settings Page
	require_once FAST_CHECKOUT_PATH . 'includes/class-settings-page.php';
	new \FastCheckout\Settings_Page();

	// Load Widget Loader
	require_once FAST_CHECKOUT_PATH . 'includes/class-widget-loader.php';
	\FastCheckout\Widget_Loader::instance();
});


