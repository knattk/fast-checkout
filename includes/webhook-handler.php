<?php
namespace FastCheckout;

use WP_REST_Request;
use WP_REST_Response;

function handle_webhook(WP_REST_Request $request) {
    $content_type = $request->get_content_type();

    if ($content_type && strpos($content_type['value'], 'application/json') !== false) {
        $data = $request->get_json_params();
    } elseif ($content_type && strpos($content_type['value'], 'application/x-www-form-urlencoded') !== false) {
        parse_str($request->get_body(), $parsed_body);
        $data = $parsed_body;
    } else {
        $data = [];
    }

    file_put_contents(WP_CONTENT_DIR . '/webhook.log', json_encode([
        'raw_body' => $request->get_body(),
        'parsed_data' => $data,
        'content_type' => $content_type,
    ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    $fields = $data['fields'] ?? [];
    $f = fn($key) => $fields[$key]['value'] ?? '';

    $full_name = trim($f('billing_full_name'));
    $space_pos = strpos($full_name, ' ');
    if ($space_pos !== false) {
        $first_name = substr($full_name, 0, $space_pos);
        $last_name = substr($full_name, $space_pos + 1);
    } else {
        $first_name = $full_name;
        $last_name = '';
    }

    $order = [
        'payment_method' => $f('payment') ?: 'cod',
        'set_paid' => ($f('payment') == 'bacs' ? false : true),
        'customer_note' => 'ทดสอบระบบไม่ต้องเก็บออเดอร์นี้ - Fast checkout landing',
        'status' => ($f('payment') == 'bacs' ? 'on-hold' : 'processing'),
        'billing' => [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address_1' => $f('billing_address_1'),
            'address_2' => $f('billing_address_2'),
            'city' => $f('billing_city'),
            'state' => $f('billing_state'),
            'postcode' => $f('billing_postcode'),
            'country' => 'TH',
            'email' => $f('billing_email'),
            'phone' => $f('billing_phone'),
        ],
        'shipping' => [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address_1' => $f('billing_address_1'),
            'address_2' => $f('billing_address_2'),
            'city' => $f('billing_city'),
            'state' => $f('billing_state'),
            'postcode' => $f('billing_postcode'),
            'country' => 'TH',
        ],
        'meta_data' => [
            ['key' => '_wc_order_attribution', 'value' => 'fast_checkout']
        ],
        'line_items' => [
            ['product_id' => absint($f('product_id') ?: 1), 'quantity' => 1]
        ]
    ];

    $store_url = get_option('fast_checkout_store_url');
    $consumer_key = fc_decrypt(get_option('fast_checkout_consumer_key'));
    $consumer_secret = fc_decrypt(get_option('fast_checkout_consumer_secret'));

    if (empty($store_url) || empty($consumer_key) || empty($consumer_secret)) {
        return new WP_REST_Response(['error' => 'Missing WooCommerce credentials'], 400);
    }

    $auth = base64_encode("$consumer_key:$consumer_secret");

    $response = wp_remote_post("$store_url/wp-json/wc/v3/orders", [
        'headers' => [
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($order),
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => $response->get_error_message()], 500);
    }

    $body = wp_remote_retrieve_body($response);
    return new WP_REST_Response(json_decode($body, true), 200);
}
