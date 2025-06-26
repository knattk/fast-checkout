<?php
namespace FastCheckout;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;

class Cart_Summary_Widget extends Widget_Base {

    protected function is_dynamic_content(): bool {
		return false;
	}
    public function get_name() {
        return 'cart_summary';
    }

    public function get_title() {
        return 'Fast Cart Summary';
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['woocommerce-elements'];
    }
    
    public function get_style_depends() {
    	return [ 'fast-cart-summary' ];
    }
    public function get_script_depends() {
    	return [ 'fast-cart-summary' ];
    }
    
     protected function register_controls() {
         
        $this->start_controls_section( 'content_section', [
            'label' => esc_html__( 'Products', 'fast-checkout' ),
        ] );

        
            $this->add_control( 'cart_title', [
                'label' => esc_html__( 'Title', 'fast-checkout' ),
                'type' => Controls_Manager::TEXT,
                'default' => 'สรุปคำสั่งซื้อ',
            ] );
    		
    		
            $this->add_control(
            	'is_show_title',
            	[
            		'label' => esc_html__( 'Hide title?', 'fast-checkout' ),
            		'type' => \Elementor\Controls_Manager::SWITCHER,
            		'return_value' => 'yes',
            		'default' => 'no',
            		
            	]
            );
        

        $this->end_controls_section();
        
        
        $this->start_controls_section(
			'style_section',
			[
				'label' => esc_html__( 'Style', 'fast-checkout' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);
		
	
                $this->add_group_control(
        			\Elementor\Group_Control_Typography::get_type(),
        			[
        				'name' => 'content_typography',
        				'selector' => '{{WRAPPER}} .fast-checkout-cart-container h4',
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
        					'{{WRAPPER}} .fast-checkout-cart-container h4' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);

            
            $this->end_controls_section();
            
            $this->start_controls_section(
            	'section_container',
            	[
            		'label' => esc_html__( 'Container', 'fast-checkout' ),
            		'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            	]
            );


                $this->add_control(
        			'container_margin',
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
        					'{{WRAPPER}} .fast-checkout-cart-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);
        		$this->add_control(
        			'container_padding',
        			[
        				'label' => esc_html__( 'Padding', 'fast-checkout' ),
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
        					'{{WRAPPER}} .fast-checkout-cart-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);
        		
        		$this->add_group_control(
        			\Elementor\Group_Control_Box_Shadow::get_type(),
        			[
        				'name' => 'container_box_shadow',
        				'selector' => '{{WRAPPER}} .fast-checkout-cart-container',
        			]
        		);
        		
        		$this->add_control(
        			'container_border_radius',
        			[
        				'label' => esc_html__( 'Border radius', 'fast-checkout' ),
        				'type' => \Elementor\Controls_Manager::DIMENSIONS,
        				'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
        				'default' => [
        					'top' => 0,
        					'right' => 0,
        					'bottom' => 1,
        					'left' => 0,
        					'unit' => 'px',
        					'isLinked' => false,
        				],
        				'selectors' => [
        					'{{WRAPPER}} .fast-checkout-cart-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);
        		
		
		$this->end_controls_section();
    }

    protected function render() { 
    
    $settings = $this->get_settings_for_display();
        
    ?>
    
        
        <div class="fast-checkout-cart-container">
            <?php if ( $settings['is_show_title'] !== 'yes' ) {  ?>
            
            <h4><?php echo esc_attr( $settings['cart_title'] ); ?></h4>
            
            <?php }  ?>
            
            <div id="fast-checkout-cart-items"></div>
        </div>
        <?php
    }
}