<?php
namespace FastCheckout\Rest;

use WP_REST_Request;
use FastCheckout\Services\Order_Service;

use function FastCheckout\Utils\get_state_code;
use function FastCheckout\Utils\get_payment_name;
use function FastCheckout\Utils\get_order_status;
use function FastCheckout\Utils\get_paid_status;





if (!defined('ABSPATH')) exit;

/**
 * เส้นทาง Order:
 * - POST /fc/v1/order/create
 *
 * ข้อกำหนดสำคัญ:
 * - ต้องมี otp_token ที่เพิ่งผ่านการ verify (one-time & short-lived)
 * - ห้ามเชื่อยอด/ราคา/ส่วนลดจาก client -> ให้ Order_Service ตัดสิน/คำนวณจริงฝั่ง server
 * - เติม meta_data (order attribution) และข้อมูลภายใน
 */
class Route_Order extends Abstract_Controller {
    private Order_Service $order;
    
    public function __construct(Order_Service $order) {
        $this->order = $order;
    }


    

    public function register_routes(): void {
        register_rest_route($this->namespace, '/order/create', [
            'show_in_index' => false, 
            'methods'  => 'POST',
            'callback' => [$this, 'order_create'],
            'permission_callback' => [$this, 'permission_public'],
            'args' => [
                'otp_token' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'payment_method' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                //     'validate_callback' => fn($v) => in_array($v, ['cod','bacs'], true),
                ],
                'customer' => ['required' => true],          // {first_name?, last_name?, full_name?, email, phone}
                'shipping_address' => ['required' => true],  // {address_1, address_2?, city, state(TH name), postcode, country?}
                'items' => ['required' => true],             // [{product_id, quantity, variation_id?}, ...]
                'csrf'  => ['required' => false],
            ],
        ]);
    }

    public function order_create(WP_REST_Request $r) {




        // 1) ใช้ OTP token แบบ one-time (มาจาก Abstract_Controller)
        $otpToken = $r->get_param('otp_token');
       // งับ fc_otptok_ ที่วางไข่ตอน verify OTP  (MD5)
        $subject  = $this->consume_otp_token($otpToken);
        if (!$subject) {
            return $this->json_error('OTP_TOKEN_INVALID', __('OTP token invalid or expired', 'fastcheckout'), 403);
        }

        // 2) รับ input หลัก
        $method   = $r->get_param('payment_method');      // 'cod'|'bacs'
        $customer = (array) $r->get_param('customer');
        $ship     = (array) $r->get_param('shipping_address');
        $items    = (array) $r->get_param('items');

        if (!$this->basic_payload_valid($customer, $ship, $items)) {
            return $this->json_error('BAD_REQUEST', __('Invalid payload', 'fastcheckout'), 422);
        }

        // 3) เตรียมชื่อ/เบอร์/อีเมล
        [$first, $last] = $this->split_name(
            $customer['full_name'] ?? ($customer['first_name'] ?? '')
        );

        $billing_first = sanitize_text_field($customer['first_name'] ?? $first);
        $billing_last  = sanitize_text_field($customer['last_name']  ?? $last);
        $billing_email = sanitize_email($customer['email'] ?? '');
        $billing_phone = $this->sanitize_phone($customer['phone'] ?? '');

        // 4) map จังหวัดไทย → state code (เช่น TH-66) ถ้าได้
        $stateCode = get_state_code($ship['state'] ?? '') ?: sanitize_text_field($ship['state'] ?? '');

        // 5) สร้าง billing/shipping (ตามรูปแบบ WooCommerce)
        $billing = [
            'first_name' => $billing_first,
            'last_name'  => $billing_last,
            'address_1'  => sanitize_text_field($ship['address_1'] ?? ''),
            'address_2'  => sanitize_text_field($ship['address_2'] ?? ''),
            'city'       => sanitize_text_field($ship['city'] ?? ''),
            'state'      => $stateCode,
            'postcode'   => sanitize_text_field($ship['postcode'] ?? ''),
            'country'    => sanitize_text_field($ship['country'] ?? 'TH'),
            'email'      => $billing_email,
            'phone'      => $billing_phone,
        ];
        $shipping = $billing;
        unset($shipping['email'], $shipping['phone']);

        // 6) กำหนด payment info: title/status/set_paid จาก utils
        $payment_method        = $method;
        $payment_method_title  = get_payment_name($method);
        $order_status          = get_order_status($method);
        $set_paid              = (bool) get_paid_status($method);

        // 7) line_items อย่างน้อย product_id + quantity (+ variation_id ได้)
        $line_items = $this->normalize_items($items);
        if (empty($line_items)) {
            return $this->json_error('BAD_REQUEST', __('No line items', 'fastcheckout'), 422);
        }

        // 8) meta_data + note (ย้ายแนวคิดจากไฟล์เก่า)
        $meta = [
            ['key' => '_wc_order_attribution_source_type', 'value' => 'referral'],
            ['key' => '_wc_order_attribution_utm_source',   'value' => home_url()],
            ['key' => '_wc_order_attribution_utm_medium',   'value' => 'referral'],
            ['key' => '_wc_order_attribution_utm_content',  'value' => 'Fast checkout'],
            ['key' => 'fc_subject',                          'value' => $subject],
            ['key' => 'fc_source',                           'value' => sanitize_text_field($_SERVER['HTTP_REFERER'] ?? '')],
        ];
        $customer_note = sprintf(
            __('สั่งซื้อผ่านเว็บไซต์ %s', 'fastcheckout'),
            parse_url(home_url(), PHP_URL_HOST)
        );

        // 9) ประกอบ payload ส่งให้ WooCommerce (Order_Service จะจัดการยิง API + คำนวณ/ตรวจราคา)
        $order_data = [
            'payment_method'       => $payment_method,
            'payment_method_title' => $payment_method_title,
            'set_paid'             => $set_paid,
            'status'               => $order_status,
            'customer_note'        => $customer_note,
            'billing'              => $billing,
            'shipping'             => $shipping,
            'meta_data'            => $meta,
            'line_items'           => $line_items,
        ];

        // 10) เรียกบริการสร้างออเดอร์
        $res = $this->order->createOrder($order_data);

        if (empty($res['ok'])) {
            return $this->json_error('ORDER_CREATE_FAILED', __('Order creation failed', 'fastcheckout'), 502, [
                'detail' => $res['error'] ?? null,
            ]);
        }

        // 11) ส่งกลับสรุป
        return $this->json_ok([
            'order_id' => (string)($res['order_id'] ?? ''),
            'summary'  => $res['summary'] ?? [
                'payment_method'   => $payment_method,
                'payment_method_title' => $payment_method_title,
                'status'           => $order_status,
                'billing'          => $this->mask_billing($billing),
                'shipping'         => $shipping,
            ],
        ]);
    }

    /* ================= Helpers ================= */

    private function basic_payload_valid($customer, $ship, $items): bool {
        if (!is_array($customer) || !is_array($ship) || !is_array($items) || empty($items)) return false;
        // อย่างน้อยควรมีที่อยู่หลัก/เมือง/รหัสไปรษณีย์ และ contact
        $hasAddress = !empty($ship['address_1']) && !empty($ship['postcode']);
        $hasContact = !empty($customer['phone']) && !empty($customer['email']);
        return $hasAddress && $hasContact;
    }

    private function split_name(string $full): array {
        $full = trim($full);
        if ($full === '') return ['', ''];
        $pos = strpos($full, ' ');
        if ($pos === false) return [$full, ''];
        return [substr($full, 0, $pos), trim(substr($full, $pos + 1))];
    }

    private function normalize_items(array $items): array {
        $out = [];
        foreach ($items as $it) {
            $pid = intval($it['product_id'] ?? 0);
            $qty = max(1, intval($it['quantity'] ?? 1));
            if ($pid <= 0) continue;
            $row = [
                'product_id' => $pid,
                'quantity'   => $qty,
            ];
            $vid = intval($it['variation_id'] ?? 0);
            if ($vid > 0) $row['variation_id'] = $vid;
            $out[] = $row;
        }
        return $out;
    }

    private function mask_billing(array $b): array {
        // ปิดบังบางส่วนก่อนส่งกลับให้ UI
        $b['phone'] = $this->mask_tail($b['phone'] ?? '');
        $b['email'] = $this->mask_email($b['email'] ?? '');
        return $b;
    }

    private function mask_tail(string $s, int $show = 2): string {
        if ($s === '') return '';
        $len = strlen($s);
        if ($len <= $show) return str_repeat('*', $len);
        return str_repeat('*', max(0, $len - $show)) . substr($s, -$show);
    }

    private function mask_email(string $email): string {
        if (!$email) return '';
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') return $this->mask_tail($email, 3);
        $localMasked = strlen($local) <= 2 ? str_repeat('*', strlen($local)) : substr($local, 0, 1) . str_repeat('*', strlen($local) - 2) . substr($local, -1);
        return $localMasked . '@' . $domain;
    }
}
