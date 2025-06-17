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
        return ['general'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'form_section',
            [
                'label' => __('Form Fields', 'fast-checkout'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control('product_id', [
            'label' => __('Product ID', 'fast-checkout'),
            'type' => Controls_Manager::NUMBER,
            'default' => 1,
        ]);

        $this->add_control('payment', [
            'label' => __('Payment Method', 'fast-checkout'),
            'type' => Controls_Manager::SELECT,
            'options' => [
                'cod' => __('Cash on Delivery', 'fast-checkout'),
                'bacs' => __('Bank Transfer', 'fast-checkout'),
            ],
            'default' => 'cod',
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <form method="post" class="custom-elementor-form" action="<?php echo esc_url(rest_url('fast-checkout/woohook/')); ?>">
            <?php wp_nonce_field('fast_checkout_nonce_action', 'fast_checkout_nonce'); ?>

            <input type="hidden" name="fields[payment][value]" value="<?php echo esc_attr($settings['payment']); ?>">
            <input type="hidden" name="fields[product_id][value]" value="<?php echo esc_attr($settings['product_id']); ?>">

            <label>Full Name</label>
            <input type="text" name="fields[billing_full_name][value]" required>

            <label>Address</label>
            <input type="text" name="fields[billing_address_1][value]" required>

            <label>Address 2</label>
            <input type="text" name="fields[billing_address_2][value]">

            <label>City</label>
            <input type="text" name="fields[billing_city][value]" required>

            <label>State</label>
            <input type="text" name="fields[billing_state][value]">

            <label>Postcode</label>
            <input type="text" name="fields[billing_postcode][value]" required>

            <label>Phone</label>
            <input type="text" name="fields[billing_phone][value]" required>

            <label>Email</label>
            <input type="email" name="fields[billing_email][value]" required>

            <button type="submit">Submit</button>
        </form>
        
        <!--<div class="form-footer">จัดส่งรวดเร็วโดย <img src="https://minicart.mychannelnews.com/wp-content/uploads/2025/05/1577-logo.png"></div>-->
<script>console.log('11')</script>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
<script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
    
<link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">
<script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>

<script>
$.Thailand({
    $district: $('#form-field-billing_address_2'), // input ของตำบล
    $amphoe: $('#form-field-billing_city'), // input ของอำเภอ
    $province: $('#form-field-billing_state'), // input ของจังหวัด
    $zipcode: $('#form-field-billing_postcode'), // input ของรหัสไปรษณีย์
});

$(function () {
    const $zipcode = $('#form-field-billing_postcode');
    const $amphoe = $('#form-field-billing_city');
    const $province = $('#form-field-billing_state');
const $address_2 = $('#form-field-billing_address_2');


    function toggleAddressFields() {
        if ($zipcode.val().trim() === '') {
            $amphoe.closest('div').hide();
            $province.closest('div').hide();
$address_2.closest('div').hide();
        } else {
            $amphoe.closest('div').show();
            $province.closest('div').show();
$address_2.closest('div').show();
        }
    }

    // Initial check
    toggleAddressFields();

    // Trigger on input change
    $zipcode.on('input', function () {
        toggleAddressFields();
    });
});

</script>
        <?php
    }
}
