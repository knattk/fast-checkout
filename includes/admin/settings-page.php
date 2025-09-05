<?php
namespace FastCheckout;

if ( ! function_exists( 'FastCheckout\\fc_encrypt' ) || ! function_exists( 'FastCheckout\\fc_decrypt' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '../utils/helpers.php';
}

class Settings_Page {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting('fast_checkout_settings', 'fast_checkout_store_url', [
            'sanitize_callback' => 'esc_url_raw',
        ]);

        register_setting('fast_checkout_settings', 'fast_checkout_consumer_key', [
            'sanitize_callback' => 'FastCheckout\\Utils\\fc_encrypt',
        ]);

        register_setting('fast_checkout_settings', 'fast_checkout_consumer_secret', [
            'sanitize_callback' => 'FastCheckout\\Utils\\fc_encrypt',
        ]);

        register_setting('fast_checkout_settings', 'fast_checkout_otp_key', [
            'sanitize_callback' => 'FastCheckout\\Utils\\fc_encrypt',
        ]);

        register_setting('fast_checkout_settings', 'fast_checkout_otp_secret', [
            'sanitize_callback' => 'FastCheckout\\Utils\\fc_encrypt',
        ]);

        register_setting('fast_checkout_settings', 'fast_checkout_allowed_ips', [
            'sanitize_callback' => 'FastCheckout\\Utils\\sanitize_ip_list',
        ]);

        // register_setting('fast_checkout_settings', 'fast_checkout_illigible_user_fallback', [
        //     'sanitize_callback' => 'wp_kses_post',
        // ]);

        // OTP provider: thaibulksms | proxy
register_setting('fast_checkout_settings', 'fast_checkout_otp_provider', [
    'sanitize_callback' => function($v){ return in_array($v, ['thaibulksms','proxy'], true) ? $v : 'thaibulksms'; },
]);

// ThaiBulkSMS base (optional override)
register_setting('fast_checkout_settings', 'fast_checkout_otp_api_base', [
    'sanitize_callback' => 'esc_url_raw',
]);

// OTP Proxy URL (if provider=proxy)
register_setting('fast_checkout_settings', 'fast_checkout_otp_proxy_url', [
    'sanitize_callback' => 'esc_url_raw',
]);

// Timeouts
register_setting('fast_checkout_settings', 'fast_checkout_otp_timeout', [
    'sanitize_callback' => 'absint',
]);
register_setting('fast_checkout_settings', 'fast_checkout_order_timeout', [
    'sanitize_callback' => 'absint',
]);

// Optional HMAC secret for order signing
register_setting('fast_checkout_settings', 'fast_checkout_hmac_secret', [
    'sanitize_callback' => 'sanitize_text_field',
]);


        add_settings_section('fast_checkout_section', 'WooCommerce API Configuration', null, 'fast_checkout_settings');

        add_settings_field('fast_checkout_store_url', 'Store URL', [$this, 'store_url_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_consumer_key', 'Consumer Key', [$this, 'consumer_key_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_consumer_secret', 'Consumer Secret', [$this, 'consumer_secret_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        
        add_settings_field('fast_checkout_allowed_ips', 'Allowed IPs (comma separated)', [$this, 'allowed_ips_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        // add_settings_field('fast_checkout_illigible_user_fallback', 'Fallback HTML (for blocked IPs)', [$this, 'illigible_user_fallback_html'], 'fast_checkout_settings', 'fast_checkout_section');
    
        add_settings_field('fast_checkout_otp_key', 'OTP Key', [$this, 'otp_key_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_otp_secret', 'OTP Secret', [$this, 'otp_secret_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_otp_provider', 'OTP Provider', [$this, 'otp_provider_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_otp_api_base', 'OTP API Base (ThaiBulkSMS)', [$this, 'otp_api_base_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_otp_proxy_url', 'OTP Proxy URL', [$this, 'otp_proxy_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_otp_timeout', 'OTP Timeout (sec)', [$this, 'otp_timeout_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_order_timeout', 'Order Timeout (sec)', [$this, 'order_timeout_field_html'], 'fast_checkout_settings', 'fast_checkout_section');
        add_settings_field('fast_checkout_hmac_secret', 'HMAC Secret (optional)', [$this, 'hmac_secret_field_html'], 'fast_checkout_settings', 'fast_checkout_section');

    }

    public function store_url_field_html() {
        $value = esc_attr(get_option('fast_checkout_store_url'));
        echo "<input type='url' name='fast_checkout_store_url' value='{$value}' size='50' placeholder='https://yourstore.com' />";
    }

    public function consumer_key_field_html() {
        $encrypted = get_option('fast_checkout_consumer_key');
        $decrypted = \FastCheckout\Utils\maybe_decrypt($encrypted);
        echo "<input type='password' name='fast_checkout_consumer_key' value='{$decrypted}' size='50' autocomplete='off' />";
    }

    public function consumer_secret_field_html() {
        $encrypted = get_option('fast_checkout_consumer_secret');
        $decrypted = \FastCheckout\Utils\maybe_decrypt($encrypted);
        echo "<input type='password' name='fast_checkout_consumer_secret' value='{$decrypted}' size='50' autocomplete='off' />";
    }

    public function otp_key_field_html() {
        $encrypted = get_option('fast_checkout_otp_key');
        $decrypted = \FastCheckout\Utils\maybe_decrypt($encrypted);
        echo "<input type='text' name='fast_checkout_otp_key' value='{$decrypted}' size='50' autocomplete='off' />";
    }

    public function otp_secret_field_html() {
        $encrypted = get_option('fast_checkout_otp_secret');
        $decrypted = \FastCheckout\Utils\maybe_decrypt($encrypted);
        echo "<input type='text' name='fast_checkout_otp_secret' value='{$decrypted}' size='50' autocomplete='off' />";
    }

    public function allowed_ips_field_html() {
        $value = esc_attr(get_option('fast_checkout_allowed_ips'));
        echo "<input type='text' name='fast_checkout_allowed_ips' value='{$value}' size='50' placeholder='192.168.1.1, 10.0.0.1' />";
    }

    public function otp_provider_field_html() {
    $val = esc_attr(get_option('fast_checkout_otp_provider', 'thaibulksms'));
    echo "<select name='fast_checkout_otp_provider'>
            <option value='thaibulksms' ".selected($val,'thaibulksms',false).">ThaiBulkSMS</option>
            <option value='proxy' ".selected($val,'proxy',false).">Proxy / Worker</option>
          </select>";
}

public function otp_api_base_field_html() {
    $v = esc_attr(get_option('fast_checkout_otp_api_base', 'https://otp.thaibulksms.com'));
    echo "<input type='url' name='fast_checkout_otp_api_base' value='{$v}' size='50' placeholder='https://otp.thaibulksms.com' />";
}

public function otp_proxy_field_html() {
    $v = esc_url(get_option('fast_checkout_otp_proxy_url', ''));
    echo "<input type='url' name='fast_checkout_otp_proxy_url' value='{$v}' size='50' placeholder='https://your-worker.example.com' />";
}

public function otp_timeout_field_html() {
    $v = intval(get_option('fast_checkout_otp_timeout', 15));
    echo "<input type='number' name='fast_checkout_otp_timeout' value='{$v}' min='5' max='60' />";
}

public function order_timeout_field_html() {
    $v = intval(get_option('fast_checkout_order_timeout', 20));
    echo "<input type='number' name='fast_checkout_order_timeout' value='{$v}' min='5' max='60' />";
}

public function hmac_secret_field_html() {
    $v = esc_attr(get_option('fast_checkout_hmac_secret', ''));
    echo "<input type='text' name='fast_checkout_hmac_secret' value='{$v}' size='50' autocomplete='off' />";
}


    // public function illigible_user_fallback_html() {
    //     $value = (get_option('fast_checkout_illigible_user_fallback'));
    //     echo '<textarea name="fast_checkout_illigible_user_fallback" rows="6" style="width: 600px">' . esc_textarea($value) . '</textarea>';
    // }

    public function add_menu() {
        add_options_page(
            'Fast Checkout Settings',
            'Fast Checkout',
            'manage_options',
            'fast-checkout',
            [$this, 'settings_page_html']
        );
    }

    public function settings_page_html() {
        $webhook_url = home_url('/wp-json/fast-checkout/v1/webhook');
        ?>
        <div class="wrap">
            <h1>Fast Checkout Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('fast_checkout_settings');
                do_settings_sections('fast_checkout_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
