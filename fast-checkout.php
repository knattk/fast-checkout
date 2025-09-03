<?php
/**
 * Plugin Name: Fast Checkout
 * Description: A custom Elementor widget plugin for WooCommerce API integration.
 * Version: 2.0.0
 * Author: Khwaaan
 * Text Domain: fast-checkout
 */

namespace FastCheckout;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p><strong>Fast Checkout</strong> requires PHP 7.4 or higher. You are running PHP ' . PHP_VERSION . '.</p></div>';
	} );
	return;
}

define( 'FAST_CHECKOUT_VERSION', '2.0.0' );
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
	require_once FAST_CHECKOUT_PATH . 'includes/admin/settings-page.php';
	new \FastCheckout\Settings_Page();

	require_once __DIR__ . '/includes/utils/helpers.php';
	require_once __DIR__ . '/includes/utils/woocommerce-utils.php';         // if used anywhere
	require_once __DIR__ . '/includes/services/otp-service.php';
	require_once __DIR__ . '/includes/services/order-service.php';

	require_once __DIR__ . '/includes/rest/abstract-controller.php';
	require_once __DIR__ . '/includes/rest/route-otp.php';
	require_once __DIR__ . '/includes/rest/route-order.php';

	require_once __DIR__.'/includes/rest/registrar.php  ';
	new \FastCheckout\Rest\Registrar();

	// Load Widget Loader
	require_once FAST_CHECKOUT_PATH . 'includes/widget-loader.php';
	\FastCheckout\Widget_Loader::instance();


	
});

