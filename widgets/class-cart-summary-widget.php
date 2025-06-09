<?php
namespace FastCheckout;

use Elementor\Widget_Base;

class Cart_Summary_Widget extends Widget_Base {

    protected function is_dynamic_content(): bool {
		return false;
	}
    public function get_name() {
        return 'cart_summary';
    }

    public function get_title() {
        return 'Cart Summary';
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['basic'];
    }
    
    public function get_style_depends() {
    	return [ 'fast-cart-summary' ];
    }
    public function get_script_depends() {
    	return [ 'fast-cart-summary' ];
    }

    protected function render() { ?>
            <div id="fast-checkout-cart-items">
                
            </div>
        
        <?php
    }
}
