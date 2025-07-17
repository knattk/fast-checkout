<?php

/**
 * Enqueue JavaScript and localize script data.
 */

add_action( 'wp_enqueue_scripts', 'order_limit_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'order_limit_enqueue_scripts' );

function order_limit_enqueue_scripts() {

    wp_register_script('ajax-verify-js',FAST_CHECKOUT_URL . 'assets/js/ajax-verify.js',[],FAST_CHECKOUT_VERSION,true
        );

    // Localize the script with the AJAX URL and a nonce for security.
    wp_localize_script( 'ajax-verify-js','ajaxVerifyLimit',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'order_limit_nonce' ),
        )
    );
    // Enqueue the script.
    wp_enqueue_script( 'ajax-verify-js' );

}


add_action('wp_ajax_order_limit', 'order_limit_ajax_handler');
add_action('wp_ajax_nopriv_order_limit', 'order_limit_ajax_handler');

function order_limit_ajax_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'order_limit_nonce')) {
        wp_send_json_error('Nonce verification failed.');
        wp_die();
    }

    $user_ip = isset($_POST['ip_address']) ? sanitize_text_field(wp_unslash($_POST['ip_address'])) : '';

    if (empty($user_ip)) {
        wp_send_json_error('IP address not provided.');
        wp_die();
    }

    $transient_key = 'fast_checkout_user_' . md5($user_ip);
    $is_not_timeout = get_transient($transient_key);

    // 
    if ($is_not_timeout) {
        $fallback_html = get_option('fast_checkout_illigible_user_fallback');
        wp_send_json_success([
            'status' => true,
            'message' =>  wp_kses_post($fallback_html) ,
        ]);
    } else {
        wp_send_json_success([
            'status' => false,
            'message' => 'IP found in transient and is not timed out.'
        ]);
    }

    wp_die();
}
