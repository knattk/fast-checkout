<?php
/**
 * Popup container template
 *
 * Use FC_Popup JS API to control.
 */
if ( ! defined( 'ABSPATH' ) ) exit;


    if (!wp_style_is('fast-checkout-popup', 'enqueued')) {
        wp_enqueue_style('fast-checkout-popup');
    }


    if (!wp_script_is('fast-checkout-popup', 'enqueued')) {
        wp_enqueue_script('fast-checkout-popup');
    }


?>
<div id="fc-popup" class="fc-popup hidden">
  <div class="fc-popup-overlay"></div>
  <div class="fc-popup-dialog">
    <button type="button" class="fc-popup-close">&times;</button>
    <div class="fc-popup-content"><!-- dynamic content --></div>
  </div>
</div>
