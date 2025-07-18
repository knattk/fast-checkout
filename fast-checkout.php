<?php
/**
 * Plugin Name: Fast Checkout
 * Description: A custom Elementor widget plugin for WooCommerce API integration.
 * Version: 1.1.4
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

define( 'FAST_CHECKOUT_VERSION', '1.1.4' );
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


	require_once FAST_CHECKOUT_PATH . 'includes/ajax-verify.php';
	
});

