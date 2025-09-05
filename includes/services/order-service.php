<?php
namespace FastCheckout\Services;

use function FastCheckout\Utils\fc_decrypt;
use function FastCheckout\Utils\get_state_name;

if (!defined('ABSPATH')) exit;

/**
 * Order_Service
 * - ส่งคำสั่งซื้อไป WooCommerce REST API v3 ของ "เว็บไซต์ภายนอก"
 * - ใส่ Basic Auth (CK/CS), timeout, retry/backoff, และ (ออปชัน) HMAC ลายเซ็น
 * - รับ $orderData เป็น payload ตามรูปแบบ WooCommerce (ดูใน route-order.php)
 *
 * คืนรูปแบบ array:
 *  - success: { ok: true, order_id: string|int, summary: array }
 *  - error:   { ok: false, error: string, http_code?: int }
 */
class Order_Service {

    private string $baseUrl;       // https://mindedge-sms-otp.mindedge.workers.dev/
    private string $ck;            // Consumer Key
    private string $cs;            // Consumer Secret
    private int    $timeout = 20;
    private ?string $hmacSecret;  
    private array  $allowed_ips;   // IP ที่อนุญาตให้เรียก (ถ้าเซ็ต)
    private string $site_url;      // เว็บนี้ (FastCheckout)
    private string $store_url;     // เว็บปลายทาง (WooCommerce)

    private int $maxRetries = 2;   // retry 2 ครั้ง (รวม 3 ครั้ง)

    public function __construct() {
        $this->baseUrl    = rtrim((string) get_option('fc_order_base_url', ''), '/');
        $this->ck         = (string) fc_decrypt(get_option('fast_checkout_consumer_key'));
        $this->cs         = (string) fc_decrypt(get_option('fast_checkout_consumer_secret'));
        $this->timeout    = max(5, intval(get_option('fc_order_timeout', 20)));
        $secret           = trim((string) get_option('fc_order_hmac_secret', ''));
        $this->hmacSecret = $secret !== '' ? $secret : null;
        // $this->allowed_ips = get_option('fast_checkout_allowed_ips', []);
        $this->store_url = get_option('fast_checkout_store_url', '');
        $this->site_url = $_SERVER['HTTP_HOST'] ?? get_site_url();


    }

    /**
     * สร้างออเดอร์ในปลายทาง
     * @param array $orderData payload ตาม Woo REST v3 /orders
     * @return array
     */
    public function createOrder(array $orderData): array {
        if (!$this->store_url || !$this->ck || !$this->cs) {
            return ['ok' => false, 'error' => 'Order service not configured'];
        }

        $url = $this->store_url . '/wp-json/wc/v3/orders';

        // Idempotency key ป้องกันกดซ้ำ (เช่น เน็ตหลุด/กด refresh)
        $idempotencyKey = $this->buildIdempotencyKey($orderData);

        $headers = [
            'Content-Type'    => 'application/json',
            'Authorization'   => 'Basic ' . base64_encode($this->ck . ':' . $this->cs),
            'Idempotency-Key' => $idempotencyKey,
        ];

        // HMAC ป้องกันแก้ไขระหว่างทาง เผื่อเว็บปลายทางตรวจ
        if ($this->hmacSecret) {
            $headers['X-FC-Signature'] = $this->signPayload($orderData);
            $headers['X-FC-Timestamp'] = (string) time();
        }

        $body = wp_json_encode($orderData);

        $attempts = 0;
        $wait     = 0.5; // sec
        do {
            $attempts++;

            $args = [
                'timeout' => $this->timeout,
                'headers' => $headers,
                'body'    => $body,
            ];

            $res = wp_remote_post($url, $args);

            if (is_wp_error($res)) {
                $errorMsg = $res->get_error_message();
                if ($attempts <= $this->maxRetries + 1 && $this->isRetryableError($errorMsg)) {
                    usleep((int)($wait * 1e6));
                    $wait *= 2;
                    continue;
                }
                return ['ok' => false, 'error' => $errorMsg];
            }

            $code = wp_remote_retrieve_response_code($res);
            $raw  = wp_remote_retrieve_body($res);
            $json = json_decode($raw, true);
            

            if ($code >= 200 && $code < 300 && is_array($json) && !empty($json['id'])) {
                // สร้างสำเร็จ
                return [
                    'ok'       => true,
                    'order_id' => $json['id'],
                    'summary'  => $this->buildSummary($json),
                ];
            }

            // 4xx/5xx — อาจลอง retry ถ้าเป็นสถานะชั่วคราว
            if ($this->shouldRetryHttp($code) && $attempts <= $this->maxRetries + 1) {
                usleep((int)($wait * 1e6));
                $wait *= 2;
                continue;
            }

            $err = $this->extractWooError($json) ?: "HTTP {$code}";
            return ['ok' => false, 'error' => $err, 'http_code' => $code];

        } while ($attempts <= $this->maxRetries + 1);

        return ['ok' => false, 'error' => 'Request failed'];
    }

    /* ================= Helpers ================= */

    private function buildIdempotencyKey(array $orderData): string {
        // ใช้ฟิลด์สำคัญทำ hash; ถ้ามี meta ที่ unique ต่อการ submit ให้รวมด้วย
        $critical = [
            $orderData['payment_method'] ?? '',
            $orderData['billing']['phone'] ?? '',
            $orderData['billing']['email'] ?? '',
            // ใช้รายการสินค้า
            wp_json_encode($orderData['line_items'] ?? []),
        ];
        return 'fc_' . md5(implode('|', $critical));
    }

    private function signPayload(array $data): string {
        $ts  = (string) time();
        $msg = $ts . '.' . wp_json_encode($data);
        return hash_hmac('sha256', $msg, $this->hmacSecret ?? '');
    }

    private function isRetryableError(string $msg): bool {
        // network timeouts, SSL issues, DNS—ให้ลองใหม่ได้
        $m = strtolower($msg);
        return str_contains($m, 'timed out')
            || str_contains($m, 'timeout')
            || str_contains($m, 'ssl')
            || str_contains($m, 'could not resolve')
            || str_contains($m, 'connection');
    }

    private function shouldRetryHttp(int $code): bool {
        // 429/5xx ให้ retry ได้
        return $code === 429 || ($code >= 500 && $code <= 599);
    }

    private function extractWooError($json): ?string {
        if (!is_array($json)) return null;
        if (!empty($json['message'])) return (string) $json['message'];
        if (!empty($json['error']))   return (string) $json['error'];
        return null;
    }

    private function buildSummary(array $wooOrder): array {
        // ดึงข้อมูลสรุปให้ฝั่ง UI
        return [
            'status'            => $wooOrder['status'] ?? '',
            'total'             => $wooOrder['total']  ?? '',
            'currency'          => $wooOrder['currency'] ?? '',
            'payment_method'    => $wooOrder['payment_method'] ?? '',
            'payment_method_title' => $wooOrder['payment_method_title'] ?? '',
            'billing' => [
                'first_name' => $wooOrder['billing']['first_name'] ?? '',
                'last_name'  => $wooOrder['billing']['last_name'] ?? '',
                'address_1'  => $wooOrder['billing']['address_1'] ?? '',
                'address_2'  => $wooOrder['billing']['address_2'] ?? '',
                'city'       => $wooOrder['billing']['city'] ?? '',
                'state'      => get_state_name($wooOrder['billing']['state']) ?? $wooOrder['billing']['state'] ?? '',
                'postcode'   => $wooOrder['billing']['postcode'] ?? '',
                'country'    => $wooOrder['billing']['country'] ?? '',
                'email'      => $wooOrder['billing']['email'] ?? '',
                'phone'      => $wooOrder['billing']['phone'] ?? '',
            ],
            'shipping' => [
                'first_name' => $wooOrder['shipping']['first_name'] ?? '',
                'last_name'  => $wooOrder['shipping']['last_name'] ?? '',
                'address_1'  => $wooOrder['shipping']['address_1'] ?? '',
                'address_2'  => $wooOrder['shipping']['address_2'] ?? '',
                'city'       => $wooOrder['shipping']['city'] ?? '',
                'state'      => $wooOrder['shipping']['state'] ?? '',
                'postcode'   => $wooOrder['shipping']['postcode'] ?? '',
                'country'    => $wooOrder['shipping']['country'] ?? '',
            ],
        ];
    }
}
