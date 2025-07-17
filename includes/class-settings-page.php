<?php
namespace FastCheckout;

if ( ! function_exists( 'FastCheckout\\fc_encrypt' ) || !function_exists( 'FastCheckout\\fc_decrypt' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '/utils.php';
}

class Settings_Page {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
        require_once plugin_dir_path(__FILE__) . 'webhook-handler.php';
    }

    public function add_menu() {
        add_options_page(
            'Fast Checkout Settings',
            'Fast Checkout',
            'manage_options',
            'fast-checkout',
            [$this, 'settings_page_html']
        );
    }

    public function register_settings() {
        register_setting('fast_checkout_settings', 'fast_checkout_store_url', [
            'sanitize_callback' => 'esc_url_raw'
        ]);

        register_setting('fast_checkout_settings', 'fast_checkout_consumer_key', [
            'sanitize_callback' => [$this, 'encrypt_consumer_key']
        ]);

        register_setting('fast_checkout_settings', 'fast_checkout_consumer_secret', [
            'sanitize_callback' => [$this, 'encrypt_consumer_secret']
        ]);
        
        register_setting('fast_checkout_settings', 'fast_checkout_allowed_ips', [
            'sanitize_callback' => [$this, 'sanitize_ip_list']
        ]);
        register_setting('fast_checkout_settings', 'fast_checkout_illigible_user_fallback', [
            'sanitize_callback' => 'wp_kses_post'
        ]);

        add_settings_section(
            'fast_checkout_section',
            'WooCommerce API Configuration',
            null,
            'fast_checkout_settings'
        );

        add_settings_field(
            'fast_checkout_store_url',
            'Store URL',
            [$this, 'store_url_field_html'],
            'fast_checkout_settings',
            'fast_checkout_section'
        );

        add_settings_field(
            'fast_checkout_consumer_key',
            'Consumer Key',
            [$this, 'consumer_key_field_html'],
            'fast_checkout_settings',
            'fast_checkout_section'
        );

        add_settings_field(
            'fast_checkout_consumer_secret',
            'Consumer Secret',
            [$this, 'consumer_secret_field_html'],
            'fast_checkout_settings',
            'fast_checkout_section'
        );
        
        add_settings_field(
            'fast_checkout_allowed_ips',
            'Allowed IPs (comma separated)',
            [$this, 'allowed_ips_field_html'],
            'fast_checkout_settings',
            'fast_checkout_section'
        );

        add_settings_field(
            'fast_checkout_illigible_user_fallback',
            'Fallback HTML (for blocked IPs)',
            [$this, 'illigible_user_fallback_html'],
            'fast_checkout_settings', // page slug
            'fast_checkout_section' // section ID
        );

    }

    public function store_url_field_html() {
        $value = esc_attr(get_option('fast_checkout_store_url'));
        echo "<input type='url' name='fast_checkout_store_url' value='{$value}' size='50' placeholder='https://yourstore.com' />";
    }

    public function consumer_key_field_html() {
        $encrypted = get_option('fast_checkout_consumer_key');
        $decrypted = $this->maybe_decrypt($encrypted);
        echo "<input type='password' name='fast_checkout_consumer_key' value='{$decrypted}' size='50' autocomplete='off' />";
    }

    public function consumer_secret_field_html() {
        $encrypted = get_option('fast_checkout_consumer_secret');
        $decrypted = $this->maybe_decrypt($encrypted);
        echo "<input type='password' name='fast_checkout_consumer_secret' value='{$decrypted}' size='50' autocomplete='off' />";
    }

    public function allowed_ips_field_html() {
        $value = esc_attr(get_option('fast_checkout_allowed_ips'));
        echo "<input type='text' name='fast_checkout_allowed_ips' value='{$value}' size='50' placeholder='192.168.1.1, 10.0.0.1' />";
    }


    public function illigible_user_fallback_html() {
        $value = (get_option('fast_checkout_illigible_user_fallback'));
        echo '<textarea name="fast_checkout_illigible_user_fallback" rows="6" style="width: 600px">' . esc_textarea($value) . '</textarea>';
    }

    public function sanitize_ip_list($value) {
        $ips = array_map('trim', explode(',', $value));
        $valid_ips = array_filter($ips, function($ip) {
            return filter_var($ip, FILTER_VALIDATE_IP);
        });
        return implode(', ', $valid_ips);
    }
    
    private function maybe_decrypt($encrypted) {
        $decrypted = '';
        if (!empty($encrypted)) {
            $decrypted = fc_decrypt($encrypted);
            if ($decrypted === false || $decrypted === null) {
                $decrypted = $encrypted;
            }
        }
        return esc_attr($decrypted);
    }

    public function encrypt_consumer_key($value) {
        return $this->encrypt_value($value);
    }

    public function encrypt_consumer_secret($value) {
        return $this->encrypt_value($value);
    }

    private function encrypt_value($value) {
        $clean = sanitize_text_field($value);
        if (empty($clean)) return '';
        if ($this->is_encrypted($clean)) return $clean;
        return fc_encrypt($clean);
    }

    private function is_encrypted($value) {
        $decoded = base64_decode($value, true);
        return $decoded !== false && strlen($decoded) > 16;
    }
    
    public function register_webhook_endpoint() {
        register_rest_route('fast-checkout/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [new \FastCheckout\WebhookHandler(), 'handle_webhook'],
            'permission_callback' => '__return_true'
        ]);
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
            <h2>Webhook URL</h2>
            <p><code><?php echo esc_html($webhook_url); ?></code></p>
        </div>
        <?php
    }
}
