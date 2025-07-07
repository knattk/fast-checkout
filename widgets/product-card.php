<?php
namespace FastCheckout;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! function_exists( __NAMESPACE__ . '\\fc_decrypt' ) ) {
    require_once plugin_dir_path( __FILE__ ) . '/utils.php';
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
        return 'eicon-accordion';
    }

    public function get_categories() {
        return [ 'woocommerce-elements' ];
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

        $repeater->add_control(
        	'is_progress_bar',
        	[
        		'label' => esc_html__( 'Progress bar?', 'fast-checkout' ),
        		'type' => \Elementor\Controls_Manager::SWITCHER,
        		'return_value' => 'yes',
        		'default' => 'no',
        		
        	]
        );
        

        $this->add_control( 'products', [
            'label' => esc_html__( 'Product List', 'fast-checkout' ),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => 'Product ID: {{ product_id }}',
            'require' => true
        ] );

        

        $this->end_controls_section();


        
        $this->start_controls_section(
			'style_section',
			[
				'label' => esc_html__( 'Heading', 'fast-checkout' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);
		
	
            $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'content_typography',
                    'selector' => '{{WRAPPER}} .product-details h3',
                ]
            );
        
            $this->add_control(
                'margin',
                [
                    'label' => esc_html__( 'Margin', 'fast-checkout' ),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                    'default' => [
                        'top' => 0,
                        'right' => 0,
                        'bottom' => 1,
                        'left' => 0,
                        'unit' => 'em',
                        'isLinked' => false,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .fast-checkout_form h4' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
            );

            
        $this->end_controls_section();

        /*
        * Container
        */

        $this->start_controls_section(
            	'section_container',
            	[
            		'label' => esc_html__( 'Container', 'fast-checkout' ),
            		'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            	]
            );


                
        		$this->add_control(
                    'gap',
                    [
                        'label' => esc_html__( 'Width', 'textdomain' ),
                        'type' => \Elementor\Controls_Manager::SLIDER,
                        'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                        'range' => [
                            'px' => [
                                'min' => 0,
                                'max' => 100,
                                'step' => 5,
                            ],
                            'rem' => [
                                'min' => 0,
                                'max' => 100,
                                'step' => 0.5,
                            ],
                        ],
                        'default' => [
                            'unit' => 'rem',
                            'size' => 0.5,
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .fast-product-card-container' => 'gap: {{SIZE}}{{UNIT}};',
                        ],
                    ]
                );
        		
		
		$this->end_controls_section();

        /*
        * Card
        */

        $this->start_controls_section(
            	'section_card',
            	[
            		'label' => esc_html__( 'Card', 'fast-checkout' ),
            		'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            	]
            );

                $this->add_control(
                    'card_bg_color',
                    [
                        'label' => esc_html__( 'BG Color', 'fast-checkout' ),
                        'type' => \Elementor\Controls_Manager::COLOR,
                        'selectors' => [
                            '{{WRAPPER}} .fast-product-card' => 'background-color: {{VALUE}}',
                        ],
                        'default' => '#ffffff',
                    ]
                );
                
        		$this->add_control(
        			'card_padding',
        			[
        				'label' => esc_html__( 'Padding', 'fast-checkout' ),
        				'type' => \Elementor\Controls_Manager::DIMENSIONS,
        				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
        				'default' => [
        					'top' => 1,
        					'right' => 1,
        					'bottom' => 1,
        					'left' => 1,
        					'unit' => 'em',
        					'isLinked' => false,
        				],
        				'selectors' => [
        					'{{WRAPPER}} .fast-product-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);
        		

        		$this->add_group_control(
        			\Elementor\Group_Control_Box_Shadow::get_type(),
        			[
        				'name' => 'card_box_shadow',
        				'selector' => '{{WRAPPER}} .fast-product-card',
        			]
        		);
        		
        		$this->add_control(
        			'card_border_radius',
        			[
        				'label' => esc_html__( 'Border radius', 'fast-checkout' ),
        				'type' => \Elementor\Controls_Manager::DIMENSIONS,
        				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
        				'default' => [
        					'top' => 4,
        					'right' => 4,
        					'bottom' => 4,
        					'left' => 4,
        					'unit' => 'px',
        					'isLinked' => false,
        				],
        				'selectors' => [
        					'{{WRAPPER}} .fast-product-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);
        		
		
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
                        <?php if ( $item['is_progress_bar'] === 'yes' ) {  ?>
                            <span class="progress-bar">
                                <span class="progress" value="70" style="width:70%">
                                    <span class="progress-text">ขายดี</span>
                                    <img src="<?php echo (plugin_dir_url( __DIR__ ).'assets/image/fire.png'); ?>" alt="">
                                </span>
                            </span>
                        <?php } ?>
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
