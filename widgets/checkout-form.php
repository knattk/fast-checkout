<?php
namespace FastCheckout;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

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
        return ['fast-cart-checkout-form'];
    }

    public function get_script_depends() {
        return ['fast-cart-checkout-form'];
    }

    protected function _register_controls() {
        $this->register_form_controls();
        $this->register_style_controls();
        $this->register_container_controls();
    }

    /**
     * Register form content controls
     */
    private function register_form_controls() {
        $this->start_controls_section(
            'form_section',
            [
                'label' => __('Form Fields', 'fast-checkout'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Product ID control
        $this->add_control('product_id', [
            'label' => __('Product ID', 'fast-checkout'),
            'type' => Controls_Manager::NUMBER,
            'default' => 1,
        ]);

        // Payment method controls
        $this->add_payment_method_controls();

        // Bank account repeater
        $this->add_bank_account_repeater();

        $this->end_controls_section();
    }

    /**
     * Add payment method controls
     */
    private function add_payment_method_controls() {
        $this->add_control(
            'is_method_cod',
            [
                'label' => esc_html__('เก็บเงินปลายทาง', 'fast-checkout'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'is_method_bacs',
            [
                'label' => esc_html__('โอนผ่านธนาคาร', 'fast-checkout'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
    }

    /**
     * Add bank account repeater control
     */
    private function add_bank_account_repeater() {
        $repeater = new Repeater();

        $repeater->add_control('bank_name', [
            'label' => esc_html__('ชื่อธนาคาร', 'fast-checkout'),
            'type' => Controls_Manager::TEXT,
        ]);

        $repeater->add_control('bank_owner', [
            'label' => esc_html__('ชื่อบัญชี', 'fast-checkout'),
            'type' => Controls_Manager::TEXT,
        ]);

        $repeater->add_control('bank_id', [
            'label' => esc_html__('เลขบัญชี', 'fast-checkout'),
            'type' => Controls_Manager::TEXT,
        ]);

        $this->add_control('bank_account', [
            'label' => esc_html__('บัญชีธนาคาร', 'fast-checkout'),
            'type' => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => 'ธนาคาร {{bank_name}}',
            'require' => true
        ]);
    }

    /**
     * Register style controls
     */
    private function register_style_controls() {
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'fast-checkout'),
                'tab' => Controls_Manager::TAB_STYLE,
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
                'label' => esc_html__('Margin', 'fast-checkout'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
    }

    /**
     * Register container style controls
     */
    private function register_container_controls() {
        $this->start_controls_section(
            'section_container',
            [
                'label' => esc_html__('Container', 'fast-checkout'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_padding',
            [
                'label' => esc_html__('Padding', 'fast-checkout'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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
                'label' => esc_html__('Border radius', 'fast-checkout'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem', 'custom'],
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

    /**
     * Render the widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $this->render_form_start();
        $this->render_billing_fields();
        $this->render_payment_methods($settings);
        $this->render_form_end();
        $this->render_external_scripts();
        $this->render_checkout_form_script();
    }

    /**
     * Render form start
     */
    private function render_form_start() {
        ?>
        <form method="post" class="fast-checkout_form" id="fast-checkout_form">
            <?php wp_nonce_field('fast_checkout_nonce_action', 'fast_checkout_nonce'); ?>
        <?php
    }

    /**
     * Render billing fields
     */
    private function render_billing_fields() {
        ?>
        <fieldset class="billing">
            <input type="hidden" name="product_id">

            <label for="billing_first_name">ชื่อ - สกุล</label>
            <input type="text" name="billing_first_name" id="billing_first_name" required value="test test">

            <div id="billing_phone_group">
                <label for="billing_phone">เบอร์โทร</label>
                <input type="tel" name="billing_phone" id="billing_phone" required value="0999999999" placeholder="เบอร์โทร">
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
        </fieldset>
        <?php
    }

    /**
     * Render payment methods
     */
    private function render_payment_methods($settings) {
        ?>
        <fieldset class="payment">
            <legend>ช่องทางชำระเงิน</legend>
            
            <?php
            $this->render_cod_payment($settings);
            $this->render_bank_transfer_payment($settings);
            ?>
        </fieldset>
        <?php
    }

    /**
     * Render COD payment method
     */
    private function render_cod_payment($settings) {
        if ($settings['is_method_cod'] !== 'yes') {
            return;
        }
        ?>
        <div class="payment-method">
            <label>
                <div class="payment-details">
                    <div class="payment-type">
                        <input type="radio" name="checkout_payment" value="cod" checked /> 
                        เก็บเงินปลายทาง
                    </div>
                    <div class="payment-desc">
                        - จ่ายเงินสด/โอน<br>
                        - จ่ายบัตรเครดิตหน้าบ้าน (เฉพาะกทมและปริมณฑล)
                    </div>
                </div>
            </label>
        </div>
        <?php
    }

    /**
     * Render bank transfer payment method
     */
    private function render_bank_transfer_payment($settings) {
        if ($settings['is_method_bacs'] !== 'yes') {
            return;
        }
        ?>
        <div class="payment-method">
            <label>
                <div class="payment-details">
                    <div class="payment-type">
                        <input type="radio" name="checkout_payment" value="bacs" /> 
                        โอนผ่านธนาคาร
                    </div>
                    <div class="payment-desc">
                        <?php $this->render_bank_accounts($settings); ?>
                    </div>
                </div>
            </label>
        </div>
        <?php
    }

    /**
     * Render bank accounts list
     */
    private function render_bank_accounts($settings) {
        echo "<ul>";
        foreach ($settings['bank_account'] as $item) {
            $bank_id = esc_attr($item['bank_id'] ?? '');
            $bank_name = esc_attr($item['bank_name'] ?? '');
            $bank_owner = esc_attr($item['bank_owner'] ?? '');
            
            if (!$bank_id) {
                continue;
            }
            
            printf(
                '<li><span class="bank_name"><strong>%s</strong></span><span class="bank_id">%s</span><span class="bank_owner">%s</span></li>',
                $bank_name,
                $bank_id,
                $bank_owner
            );
        }
        echo "</ul>";
    }

    /**
     * Render form end
     */
    private function render_form_end() {
        ?>
        <button type="submit" id="fast-checkout-submit">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#f7f7f7" viewBox="0 0 256 256"><path d="M184,128a246.64,246.64,0,0,1-18.54,94.24,8,8,0,0,1-7.4,5,8.19,8.19,0,0,1-3-.6,8,8,0,0,1-4.36-10.45A230.67,230.67,0,0,0,168,128a8,8,0,0,1,16,0ZM128,88a40.06,40.06,0,0,1,29.81,13.33,8,8,0,1,0,11.92-10.67A56,56,0,0,0,72,128a136.06,136.06,0,0,1-17,65.85,8,8,0,1,0,14,7.76A152.14,152.14,0,0,0,88,128,40,40,0,0,1,128,88Zm0-64a103.75,103.75,0,0,0-34.67,5.92A8,8,0,0,0,98.67,45,88.05,88.05,0,0,1,216,128a281.31,281.31,0,0,1-6.94,62.23,8,8,0,0,0,6,9.57,7.77,7.77,0,0,0,1.78.2,8,8,0,0,0,7.8-6.23A298.11,298.11,0,0,0,232,128,104.11,104.11,0,0,0,128,24ZM69.34,62.42A8,8,0,1,0,58.67,50.49,104.16,104.16,0,0,0,24,128a87.29,87.29,0,0,1-8,36.66,8,8,0,0,0,14.54,6.68A103.17,103.17,0,0,0,40,128,88.13,88.13,0,0,1,69.34,62.42Zm44.58,138.32a8,8,0,0,0-10.61,3.93c-1.92,4.2-4,8.39-6.29,12.44A8,8,0,0,0,100.14,228a7.88,7.88,0,0,0,3.87,1,8,8,0,0,0,7-4.12c2.44-4.41,4.74-9,6.84-13.52A8,8,0,0,0,113.92,200.74ZM128,120a8,8,0,0,0-8,8,185.07,185.07,0,0,1-5.79,46,8,8,0,0,0,5.75,9.74,8.12,8.12,0,0,0,2,.25,8,8,0,0,0,7.74-6,200.68,200.68,0,0,0,6.3-50A8,8,0,0,0,128,120Z"></path></svg> 
            ยืนยันการสั่งซื้อ
        </button>
        </form>
        <?php
    }

    /**
     * Render external script dependencies
     */
    private function render_external_scripts() {
        ?>
        <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
        <script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
        <link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">
        <script src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>
        <?php
    }

    /**
     * Render checkout form script
     */
    private function render_checkout_form_script() {
        $webhook_url = esc_url(rest_url("fast-checkout/v1/webhook"));
        ?>
        <script>
            // Pass PHP data to JavaScript
            window.fastCheckoutConfig = {
                webhookUrl: '<?php echo $webhook_url; ?>'
            };
        </script>
        <?php
    }
}