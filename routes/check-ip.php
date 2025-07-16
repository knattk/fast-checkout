<?php
namespace FastCheckout;

use WP_REST_Request;
use WP_REST_Response;

add_action('rest_api_init', function() {
    register_rest_route('fast-checkout/v1', '/check-ip', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ .'\\fast_checkout_check_ip_timeout',
        'permission_callback' => '__return_true', // public endpoint
    ]);
});

function fast_checkout_check_ip_timeout(\WP_REST_Request $request) {
    $params = $request->get_json_params();
    $ip = sanitize_text_field($params['ip'] ?? '');
    $widget = new \FastCheckout\Checkout_Form_Widget();

    if (empty($ip)) {
        return new WP_REST_Response([
            'allowed' => false,
            'fallback_html' => '<p>Could not verify your IP.</p>',
        ], 400);
    }

    $transient_key = 'fast_checkout_user_' . md5($ip);
    $has_timed_out = get_transient($transient_key) !== false;

    if ($has_timed_out) {
        // User blocked, show fallback
       
        ob_start();
        $widget->render_form_start();
        $widget->render_billing_fields();
        $widget->render_payment_methods($widget->get_settings_for_display());
        $widget->render_policy_fields($widget->get_settings_for_display());
        $widget->render_form_end();
        $widget->render_checkout_form_script();
        $fallback_html = ob_get_clean();
        
        return [
            'allowed' => false,
            'fallback_html' => $fallback_html,
        ];
    }

    // User allowed, render the form HTML (you can reuse widget methods or build HTML here)
    // For demo, just a simple form snippet:
    


    // If your widget needs settings, pass them here or set defaults
    // For example, if get_settings_for_display() depends on some settings, you may need to mock or inject those

    ob_start();
    $widget->render_form_start();
    $widget->render_billing_fields();
    $widget->render_payment_methods($widget->get_settings_for_display());
    $widget->render_policy_fields($widget->get_settings_for_display());
    $widget->render_form_end();
    $widget->render_checkout_form_script();
    $form_html = ob_get_clean();

    return [
        'allowed' => true,
        'form_html' => $form_html,
    ];
}

