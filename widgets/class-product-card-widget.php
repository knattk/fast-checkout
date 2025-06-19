<?php
namespace FastCheckout;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! function_exists( __NAMESPACE__ . '\\fc_decrypt' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '/sec.php';
}

use function FastCheckout\fc_decrypt;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Product_Card_Widget extends Widget_Base {
    
    protected function is_dynamic_content(): bool {
		return false;
	}

    public function get_name() {
        return 'product_card';
    }

    public function get_title() {
        return esc_html__( 'Fast Product Card', 'fast-checkout' );
    }

    public function get_icon() {
        return 'eicon-product-box';
    }

    public function get_categories() {
        return [ 'basic' ];
    }
    
    public function get_style_depends() {
	    return [ 'fast-checkout-card' ];
    }
    
    public function get_script_depends() {
    	return [ 'fast-checkout-card' ];
    }

    protected function register_controls() {
        $this->start_controls_section( 'content_section', [
            'label' => esc_html__( 'Products', 'fast-checkout' ),
        ] );

        $this->add_control( 'form_id', [
            'label' => esc_html__( 'Form ID', 'fast-checkout' ),
            'type' => Controls_Manager::TEXT,
            'default' => '#fast_checkout',
        ] );

        $this->add_control( 'field_id', [
            'label' => esc_html__( 'Field ID', 'fast-checkout' ),
            'type' => Controls_Manager::TEXT,
            'default' => 'product_id',
        ] );

        $this->add_control( 'primary_button_text', [
            'label' => esc_html__( 'Button text', 'fast-checkout' ),
            'type' => Controls_Manager::TEXT,
            'default' => 'สั่งซื้อราคาพิเศษ',
        ] );
		
		
        $this->add_control(
        	'is_custom_shop_url',
        	[
        		'label' => esc_html__( 'More button?', 'fast-checkout' ),
        		'type' => \Elementor\Controls_Manager::SWITCHER,
        		'return_value' => 'yes',
        		'default' => 'no',
        		
        	]
        );
        
        $this->add_control( 'custom_shop_button', [
            'label' => esc_html__( 'Custom shop button', 'fast-checkout' ),
            'type' => Controls_Manager::TEXT,
            'default' => 'สั่งซื้อผ่าน Shopee',
            'condition' => [
			    'is_custom_shop_url' => 'yes',
		    ],
        ] );
        
        
        $repeater = new Repeater();

        $repeater->add_control( 'product_id', [
            'label' => esc_html__( 'Product ID', 'fast-checkout' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 1,
            'default' => '',
            'require' => true,
        ] );

        $repeater->add_control( 'product_image', [
            'label' => __( 'Product Image', 'fast-checkout' ),
            'type' => Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src(),
            ],
        ] );

        $repeater->add_control( 'product_short_name', [
            'label' => esc_html__( 'Product short name', 'fast-checkout' ),
            'type' => Controls_Manager::TEXT,
        ] );

        $repeater->add_control( 'product_like', [
            'label' => esc_html__( 'Product Like', 'fast-checkout' ),
            'type' => Controls_Manager::NUMBER,
            'min' => 1000,
            'default' => '1423',
        ] );
        
        $repeater->add_control( 'product_custom_shop_url', [
            'label' => esc_html__( 'Shop URL', 'fast-checkout' ),
            'type' => \Elementor\Controls_Manager::URL,
            'options' => false,
        ] );

        $this->add_control( 'products', [
            'label' => esc_html__( 'Product List', 'fast-checkout' ),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => 'Product ID: {{ product_id }}',
            'require' => true
        ] );

        $this->end_controls_section();
    }

    private function formatNumberShort($number) {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        } else {
            return (string)$number;
        }
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        if ( empty( $settings['products'] ) ) {
            echo '<p>' . esc_html__( 'No products added.', 'fast-checkout' ) . '</p>';
            return;
        }

        // API credentials from settings
        $store_url       = rtrim( get_option('fast_checkout_store_url'), '/' );
        $consumer_key    = fc_decrypt( get_option('fast_checkout_consumer_key') );
        $consumer_secret = fc_decrypt( get_option('fast_checkout_consumer_secret') );
        $keysubstr = substr( $consumer_key, 0, 4 );

        if ( ! $store_url || ! $consumer_key || ! $consumer_secret ) {
            echo '<p>' . esc_html__( 'API credentials not configured.', 'fast-checkout' ) . '</p>';
            return;
        }

        echo '<div class="fast-product-card-container">';

        foreach ( $settings['products'] as $item ) {
            $product_id = absint( $item['product_id'] ?? 0 );
            $image_url  = esc_url( $item['product_image']['url'] ?? '' );
            $custom_shop_url = esc_url( $item['product_custom_shop_url']['url'] ?? '#' );
            $product_like = $this->formatNumberShort($item['product_like']);

            if ( ! $product_id ) continue;

            // Caching
      
            $cache_key = 'fast_checkout_product_' . $keysubstr . $product_id;
            $body = get_transient( $cache_key );

            if ( false === $body ) {
           
                $api_url = "{$store_url}/wp-json/wc/v3/products/{$product_id}";
                $response = wp_remote_get( $api_url, [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode( trim($consumer_key) . ':' . trim($consumer_secret) ),
                    ],
                    'timeout' => 10,
                ] );

                if ( is_wp_error( $response ) ) {
                    continue;
                }

                $body = json_decode( wp_remote_retrieve_body( $response ), true );

                if ( ! empty( $body ) && is_array( $body ) ) {
                    set_transient( $cache_key, $body, 3600 );
                }
            }

            if ( !empty( $body['name'] ) ) {
                ?>
                <div class="fast-product-card" data-product_id="<?php echo esc_attr( $product_id ); ?>">

                    <div class="product-image">
                        <img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr( $body['name'] ); ?>">
                        <span class="product-progress-bar">
                         <span class="progress" value="0" style="width:0%"><span class="progress-text"></span><img src="<?php echo (plugin_dir_url( __DIR__ ).'asssets/image/fire.png') ?>" alt=""></span>
                         </span>
                    </div>
                    <div class="product-details">
                        <h3><?php echo !empty($item['product_short_name']) ? $item['product_short_name'] : esc_html($body['name']); ?></h3>
                        <p class="product-price"><?php echo wp_kses_post( $body['price_html'] ?? '$' . $body['price'] ); ?></p>
                        <span class="product-like"><?php echo esc_html( $product_like ); ?> คนสนใจ</span>
                        <div class="buttons">
                            <?php if ( $settings['is_custom_shop_url'] === 'yes' ) {  ?>
                                    
                                     <a class="outbound-shop" href="<?php echo esc_attr( $custom_shop_url ); ?>" target="_blank" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-field_id="<?php echo esc_attr( $settings['field_id'] ); ?>">
                                        <?php echo esc_html( $settings['custom_shop_button'] ); ?>
                                    </a>
                                    
                            <?php } ?>
                            
                            <a class="add-to-cart-btn" href="<?php echo esc_attr( $settings['form_id'] ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-field_id="<?php echo esc_attr( $settings['field_id'] ); ?>">
                                <?php echo esc_html($settings['primary_button_text']); ?>
                            </a>
                            
                            
                           
                        </div>
                        
                        
                    </div>

                </div>
                <?php } else { ?>
                    
                    <p>No product.</p>
                
                <?php 
                    
                    } } echo '</div>';
    }
}
