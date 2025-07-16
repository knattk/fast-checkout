<?php
namespace FastCheckout;

use WP_REST_Request;
use WP_REST_Response;

if ( ! function_exists( 'FastCheckout\\get_state_code' ) || ! function_exists( 'FastCheckout\\get_payment_name') || ! function_exists( 'FastCheckout\\get_paid_status') || ! function_exists( 'FastCheckout\\get_order_status'  ) ) {
    require_once plugin_dir_path( __FILE__ ) . '/woocommerce-utils.php';
}
if ( ! function_exists( 'FastCheckout\\fc_encrypt' ) || !function_exists( 'FastCheckout\\fc_decrypt' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '/utils.php';
}

class WebhookHandler {
    
    private $allowed_ips;
    private $store_url;
    private $consumer_key;
    private $consumer_secret;
    private $site_url;
    
    public function __construct() {
        $this->allowed_ips = get_option('fast_checkout_allowed_ips');
        $this->store_url = get_option('fast_checkout_store_url');
        $this->consumer_key = fc_decrypt(get_option('fast_checkout_consumer_key'));
        $this->consumer_secret = fc_decrypt(get_option('fast_checkout_consumer_secret'));
        $this->site_url = $_SERVER['HTTP_HOST'] ?? get_site_url();
    }
    
    public function handle_webhook(WP_REST_Request $request) {
        try {
            // Validate request
            $validation_result = $this->validate_request($request);
            if (is_wp_error($validation_result)) {
                return $validation_result;
            }
            
            // Parse request data
            $data = $this->parse_request_data($request);
            $this->log_webhook_data($request, $data);
            

            

            // Check nouce
            $nonce = $data['fast_checkout_nonce'] ?? null;
            if (! $nonce || ! wp_verify_nonce($nonce, 'fast_checkout_nonce_action')) {
                return new \WP_REST_Response(['error' => 'Invalid nonce'], 403);
            }

            // Check illigible user
            // $user_ip = $data['user_ip'] ?? null;
            // $transient_key = 'fast_checkout_user_' . md5($user_ip);
            // $is_user_exit = get_transient($transient_key);

            // if (false === $is_user_exit) {
                
            // } else {
               
            // }



            file_put_contents(
                WP_CONTENT_DIR . '/form-data.log', 
                json_encode($data, JSON_PRETTY_PRINT) . "\n", 
                FILE_APPEND
            );

            // Create order data
            $order_data = $this->build_order_data($data);
            
            // Send to WooCommerce
            $response = $this->send_to_woocommerce($order_data);
            
            $this->set_db_transient_key($data);

            return $response;
            
        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            return new WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }
    
    // Store user log with wp transient to support order limit per user
    private function set_db_transient_key($order_data) {
            // Get IP address
            $ip_address = $order_data['user_ip'] ?? null;
            $limit_timeout_hours = (int) ($order_data['limit_timeout_hours'] ?? 72);
            $user_agent = $order_data['user_agent'] ?? null;
            $user_phone = !empty($order_data['fields']['billing_phone']['value']) ? md5(trim($order_data['fields']['billing_phone']['value'])) : null;
            $user_email = !empty($order_data['fields']['billing_email']['value']) ? md5(trim($order_data['fields']['billing_email']['value'])) : null;
            // Generate transient key
            $transient_key = 'fast_checkout_user_' .  md5($ip_address); //md5($ip_address)
            $transient_data = [
                // 'created_at' => time(),
                'user_agent' => $user_agent,
                'user_phone' => $user_phone,
                'user_email' => $user_email,
                'limit_timeout_hours' => $limit_timeout_hours * HOUR_IN_SECONDS
            ];

            if (!$ip_address) {
                error_log('Fast Checkout: Missing IP address for transient key.');
                return;
            }
            // Save IP and timestamp in transient for 24 hours
	        set_transient( $transient_key, $transient_data, $limit_timeout_hours * HOUR_IN_SECONDS );
    }

    // validate IP
    private function validate_request(WP_REST_Request $request) {
        if (empty($this->allowed_ips)) {
            return true; // Skip validation if no IPs configured
        }
        
        $allowed_ips_array = array_map('trim', explode(',', $this->allowed_ips));
        $remote_ip = $request->get_header('cf-connecting-ip') ?: ($_SERVER['REMOTE_ADDR'] ?? '');
        
        if (!in_array($remote_ip, $allowed_ips_array)) {
            return new WP_Error('forbidden', 'Access denied', ['status' => 403]);
        }
        
        return true;
    }
    
    // parse data
    private function parse_request_data(WP_REST_Request $request) {
        $content_type = $request->get_content_type();
        
        if ($content_type && strpos($content_type['value'], 'application/json') !== false) {
            return $request->get_json_params();
        } elseif ($content_type && strpos($content_type['value'], 'application/x-www-form-urlencoded') !== false) {
            parse_str($request->get_body(), $parsed_body);
            return $parsed_body;
        }
        
        return [];
    }
    
    // create order data
    private function build_order_data($data) {
        $fields = $data['fields'] ?? [];
        $f = fn($key) => $fields[$key]['value'] ?? '';
        
        $billing_info = $this->parse_billing_info($f);
        $payment_info = $this->parse_payment_info($f);
        
        return [
            'payment_method' => $payment_info['method'],
            'payment_method_title' => $payment_info['title'],
            'set_paid' => $payment_info['paid_status'],
            'customer_note' => 'สั่งซื้อผ่านเว็บไซต์ ' . $this->site_url,
            'status' => $payment_info['order_status'],
            'billing' => $billing_info['billing'],
            'shipping' => $billing_info['shipping'],
            'meta_data' => $this->build_meta_data(),
            'line_items' => [
                ['product_id' => absint($f('product_id') ?: 1), 'quantity' => 1]
            ]
        ];
    }
    
    // parse billing data
    private function parse_billing_info($f) {
        $full_name = trim($f('billing_full_name'));
        $space_pos = strpos($full_name, ' ');
        
        $first_name = $space_pos !== false ? substr($full_name, 0, $space_pos) : $full_name;
        $last_name = $space_pos !== false ? substr($full_name, $space_pos + 1) : '';
        
        $state_code = get_state_code($f('billing_state'));
        
        $address_data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address_1' => $f('billing_address_1'),
            'address_2' => $f('billing_address_2'),
            'city' => $f('billing_city'),
            'state' => $state_code,
            'postcode' => $f('billing_postcode'),
            'country' => 'TH',
        ];
        
        return [
            'billing' => array_merge($address_data, [
                'email' => $f('billing_email'),
                'phone' => $f('billing_phone'),
            ]),
            'shipping' => $address_data
        ];
    }
    
    // parse payment data
    private function parse_payment_info($f) {
        $payment_method = $f('payment') ?: 'cod';
        
        return [
            'method' => $payment_method,
            'title' => get_payment_name($payment_method),
            'paid_status' => get_paid_status($payment_method),
            'order_status' => get_order_status($payment_method)
        ];
    }
    
    // meta data
    private function build_meta_data() {
        return [
            ['key' => '_wc_order_attribution_source_type', 'value' => 'referral'],
            ['key' => '_wc_order_attribution_utm_source', 'value' => $this->site_url],
            ['key' => '_wc_order_attribution_utm_medium', 'value' => 'referral'],
            ['key' => '_wc_order_attribution_utm_content', 'value' => 'Fast checkout']
        ];
    }
    
    // send order data to WooCommerce
    private function send_to_woocommerce($order_data) {
        if (empty($this->store_url) || empty($this->consumer_key) || empty($this->consumer_secret)) {
            throw new Exception('Missing WooCommerce credentials');
        }
        
        $auth = base64_encode("{$this->consumer_key}:{$this->consumer_secret}");
        
        $response = wp_remote_post("{$this->store_url}/wp-json/wc/v3/orders", [
            'headers' => [
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($order_data),
            'timeout' => 60,
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $this->log_woocommerce_response($body);
        
        return new WP_REST_Response(json_decode($body, true), 200);
    }
    
    // Logging methods
    private function log_webhook_data(WP_REST_Request $request, $data) {
        $log_data = [
            'timestamp' => current_time('mysql'),
            'raw_body' => $request->get_body(),
            'parsed_data' => $data,
            'content_type' => $request->get_content_type(),
        ];
        
        file_put_contents(
            WP_CONTENT_DIR . '/webhook.log', 
            json_encode($log_data, JSON_PRETTY_PRINT) . "\n", 
            FILE_APPEND
        );
    }
    
    private function log_woocommerce_response($response_body) {
        $log_data = [
            'timestamp' => current_time('mysql'),
            'woocommerce_response' => $response_body,
            'decoded_response' => json_decode($response_body, true),
        ];
        
        $log_path = WP_CONTENT_DIR . '/webhook.log';
        if (is_writable(WP_CONTENT_DIR)) {
            file_put_contents($log_path, json_encode($log_data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        } else {
            error_log('FastCheckout: Cannot write to log file at ' . $log_path);
        }

        // file_put_contents(
        //     WP_CONTENT_DIR . '/webhook.log', 
        //     json_encode($log_data, JSON_PRETTY_PRINT) . "\n", 
        //     FILE_APPEND
        // );
    }
    
    private function log_error($error_message) {
        error_log("FastCheckout Webhook Error: {$error_message}");
    }

    /**
     * Get user agent with sanitization
     */
    private function get_user_agent(WP_REST_Request $request) {
        $user_agent = $request->get_header('user-agent') ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // Sanitize user agent to prevent log injection
        return sanitize_text_field($user_agent);
    }

    private function get_user_ip(WP_REST_Request $request) {
        // Check for IP from various headers in order of preference
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_CLIENT_IP',            // Shared internet
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    
        // Fallback to REMOTE_ADDR if no valid public IP found
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

}

