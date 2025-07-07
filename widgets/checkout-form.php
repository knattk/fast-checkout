<?php
namespace FastCheckout;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Checkout_Form_Widget extends Widget_Base {

    public function get_name() {
        return 'custom_form_widget';
    }

    public function get_title() {
        return __('Fast Checkout Form', 'fast-checkout');
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return ['woocommerce-elements'];
    }
    public function get_style_depends() {
    	return [ 'fast-cart-checkout-form' ];
    }
    public function get_script_depends() {
    	return [ 'fast-cart-checkout-form' ];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'form_section',
            [
                'label' => __('Form Fields', 'fast-checkout'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // $this->add_control('product_id', [
        //     'label' => __('Product ID', 'fast-checkout'),
        //     'type' => Controls_Manager::NUMBER,
        //     'default' => 1,
        // ]);

        // $this->add_control('payment', [
        //     'label' => __('Payment Method', 'fast-checkout'),
        //     'type' => Controls_Manager::SELECT,
        //     'options' => [
        //         'cod' => __('Cash on Delivery', 'fast-checkout'),
        //         'bacs' => __('Bank Transfer', 'fast-checkout'),
        //     ],
        //     'default' => 'cod',
        // ]);

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
        				'selector' => '{{WRAPPER}} .fast-checkout_form h4',
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
            
            $this->start_controls_section(
            	'section_container',
            	[
            		'label' => esc_html__( 'Container', 'fast-checkout' ),
            		'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            	]
            );


                
        		$this->add_control(
        			'container_padding',
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
        					'{{WRAPPER}} .fast-checkout_form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);
        		
        		$this->add_group_control(
        			\Elementor\Group_Control_Box_Shadow::get_type(),
        			[
        				'name' => 'container_box_shadow',
        				'selector' => '{{WRAPPER}} .fast-checkout_form',
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
        					'{{WRAPPER}} .fast-checkout_form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
        				],
        			]
        		);
        		
		
		$this->end_controls_section();

    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <form method="post" class="fast-checkout_form" id="fast-checkout_form">
            <?php wp_nonce_field('fast_checkout_nonce_action', 'fast_checkout_nonce'); ?>

            <div class="billing">
                
                <input type="hidden" name="product_id">

                <label for="billing_first_name">ชื่อ - สกุล</label>
                <input type="text" name="billing_first_name" id="billing_first_name" required value="test test">

                <div id="billing_phone_group">
                    <label for="billing_phone">เบอร์โทร</label>
                    <input type="text" name="billing_phone" id="billing_phone" required value="0999999999" placeholder="เบอร์โทร">
                </div>
                <div id="billing_email_group">
                    <label for="billing_email">อีเมล</label>
                    <input type="email" name="billing_email" id="billing_email" required value="nattakanc@mindedge.co.th" placeholder="อีเมลรับอัพเดทสถานะสั่งซื้อ">
                </div>
                <div id="billing_address_1_group">
                    <label for="billing_address_1">บ้านเลขที่ / ซอย / ถนน</label>
                    <input type="text" name="billing_address_1" id="billing_address_1" required value="tese" placeholder="บ้านเลขที่ / ซอย / ถนน">
                </div>
                <div id="billing_postcode_group">
                    <label for="billing_postcode">รหัสไปรษณีย์</label>
                    <input type="text" name="billing_postcode" id="billing_postcode" required placeholder="รหัสไปรษณีย์">
                </div>
                <div id="billing_address_2_group">
                    <label for="billing_address_2">แขวง / ตำบล</label>
                    <input type="text" name="billing_address_2" id="billing_address_2" placeholder="แขวง/ตำบล">
                </div>
                <div id="billing_city_group">
                    <label for="billing_city">เขต / อำเภอ</label>
                    <input type="text" name="billing_city" id="billing_city" required placeholder="เขต/อำเภอ">
                </div>
                <div id="billing_state_group">
                    <label for="billing_state">จังหวัด</label>
                    <input type="text" name="billing_state" id="billing_state" placeholder="จังหวัด">
                </div>      
                
               
            </div>
            
            <div class="payment">
                 <label for="checkout_payment">ช่องทางชำระเงิน</label>
                <select name="checkout_payment">
                    <option value="cod" selected>จ่ายเงินหน้าบ้าน</option>
                    <option value="bacs">โอนผ่านบัญชีธนาคาร</option>
                </select>
            </div>
            <button type="submit">ยืนยันการสั่งซื้อ</button>
        </form>
        
<!-- Keep jQuery only for jquery.Thailand.js -->
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
<script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
<link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">
<script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>

<script>
    // Pure JavaScript: Form submit
    document.getElementById('fast-checkout_form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const form = e.target;
        let storeUrl = null;

        const data = {
            fast_checkout_nonce: form.fast_checkout_nonce.value,
            fields: {
                payment: { value: form.checkout_payment.value },
                product_id: { value: form.product_id.value },
                billing_full_name: { value: form.billing_first_name.value },
                billing_address_1: { value: form.billing_address_1.value },
                billing_address_2: { value: form.billing_address_2.value },
                billing_city: { value: form.billing_city.value },
                billing_state: { value: form.billing_state.value },
                billing_postcode: { value: form.billing_postcode.value },
                billing_phone: { value: form.billing_phone.value },
                billing_email: { value: form.billing_email.value },
            },
        };

        try {
            const response = await fetch('<?php echo esc_url(rest_url("fast-checkout/v1/webhook")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();
            console.log('Raw fetch response:', response);
            console.log('Parsed JSON result:', result);

            storeUrl = getBaseUrl(result['payment_url']);

            function getBaseUrl(url) {
                return url.replace(/^(https?:\/\/[^/]+).*/, '$1/');
            }

            console.log(
                `${storeUrl}checkout/order-received/${result['id']}/?key=${result['order_key']}`
            );
        } catch (err) {
            console.log(err);
        }
    });

    // jQuery is required for $.Thailand — leave it here
    $.Thailand({
        $district: $('#billing_address_2'),
        $amphoe: $('#billing_city'),
        $province: $('#billing_state'),
        $zipcode: $('#billing_postcode'),
    });

    // Pure JavaScript: Hide/show address fields based on postcode
    document.addEventListener('DOMContentLoaded', function () {
        const zipcode = document.getElementById('billing_postcode');
        const amphoe = document.getElementById('billing_city');
        const province = document.getElementById('billing_state');
        const address2 = document.getElementById('billing_address_2');

        function toggleAddressFields() {
            const val = zipcode.value.trim();

            [amphoe, province, address2].forEach(el => {
                const wrapper = el.closest('div'); // Adjust if your wrapper is <p> or something else
                if (wrapper) {
                    wrapper.style.display = val === '' ? 'none' : 'block';
                }
            });
        }

        // Initial state
        toggleAddressFields();

        // Listen for input changes
        zipcode.addEventListener('input', toggleAddressFields);
    });
</script>


        <?php
    }
}
