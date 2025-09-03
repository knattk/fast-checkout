<?php
namespace FastCheckout\Services;

if (!defined('ABSPATH')) exit;

/**
 * OTP_Service
 *  - รองรับ 2 โหมด: เรียก ThaiBulkSMS โดยตรง หรือผ่าน Proxy/Worker
 *  - ให้ผลลัพธ์เป็น array มาตรฐาน: { ok: bool, refno?: string, phone?: string, cooldown?: int, error?: string }
 */
class OTP_Service {

    private string $provider;          // 'thaibulksms' | 'proxy'
    private string $apiBase;           // https://api.thaibulksms.com (เมื่อ provider=thaibulksms)
    private string $apiKey;            // ThaiBulkSMS key
    private string $apiSecret;         // ThaiBulkSMS secret
    private string $proxyUrl;          // Worker/Proxy endpoint (เมื่อ provider=proxy)

    private int $timeout = 15;

    public function __construct() {
        $this->provider  = get_option('fast_checkout_otp_provider', 'thaibulksms');
        $this->apiBase   = rtrim(get_option('fast_checkout_otp_api_base', 'https://api.thaibulksms.com'), '/');
        $this->apiKey    = (string) get_option('fast_checkout_otp_key', '');
        $this->apiSecret = (string) get_option('fast_checkout_otp_secret', '');
        $this->proxyUrl  = rtrim((string) get_option('fast_checkout_otp_proxy_url', ''), '/');

        $t = intval(get_option('fast_checkout_otp_timeout', 15));
        if ($t > 0) $this->timeout = $t;
    }

    /**
     * ขอรหัส OTP ไปยังหมายเลขโทรศัพท์
     * @param string $phone  เบอร์ที่ส่ง (digits only)
     * @param array  $meta   ข้อมูลเสริม เช่น fingerprint
     * @return array { ok, refno?, cooldown?, error? }
     */
    public function requestOTP(string $msisdn, array $meta = []): array {
        if ($this->provider === 'proxy' && $this->proxyUrl) {
            return $this->requestViaProxy($msisdn, $meta);
        }
        return $this->requestViaThaiBulkSMS($msisdn, $meta);
    }

    /**
     * ยืนยันรหัส OTP
     * @param string $refno รหัสอ้างอิงจากตอน request
     * @param string $code  รหัส OTP ที่ผู้ใช้กรอก
     * @return array { ok, phone?, error? }
     */
    public function verifyOTP(string $token, string $pin): array {
        if ($this->provider === 'proxy' && $this->proxyUrl) {
            return $this->verifyViaProxy($token, $pin);
        }
        return $this->verifyViaThaiBulkSMS($token, $pin);
    }

    /* ================= Implementation: Proxy/Worker ================= */

    private function requestViaProxy(string $msisdn, array $meta): array {
        $url = $this->proxyUrl . '/api/otp/request';
        $body = [
            'key' => $this->apiKey,
            'secret' => $this->apiSecret,
            'msisdn' => $msisdn,
        ];
        $args = [
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => wp_json_encode($body),
        ];
        $res = wp_remote_post($url, $args);
        return $this->normalizeProxyResponse($res, 'request');
    }

    private function verifyViaProxy(string $token, string $pin): array {
        $url = $this->proxyUrl . '/api/otp/verify';
        $body = [
            'key' => $this->apiKey,
            'secret' => $this->apiSecret,
            'token' => $token,
            'pin'   => $pin,
        ];
        $args = [
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => wp_json_encode($body),
        ];
        $res = wp_remote_post($url, $args);
        return $this->normalizeProxyResponse($res, 'verify');
    }

    private function normalizeProxyResponse($res, string $mode): array {
        if (is_wp_error($res)) {
            return ['ok' => false, 'error' => $res->get_error_message()];
        }
        $code = wp_remote_retrieve_response_code($res);
        $raw  = wp_remote_retrieve_body($res);
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            return ['ok' => false, 'error' => "Bad proxy response ({$code})"];
        }
        if ($mode === 'request') {
            return [
                'ok'       => !empty($json['ok']),
                'token'    => $json['token'] ?? null,
                'refno'    => $json['refno'] ?? null,
                'cooldown' => $json['cooldown'] ?? 60,
                'error'    => $json['error'] ?? null,
            ];
        }

        if ($mode === 'verify') {
            return [
                'ok'    => !empty($json['ok']),
                'status' => $json['status'] ?? null,
                'message' => $json['message'] ?? null,
                'error' => $json['error'] ?? null,
            ];
        }

        return [
            'ok'    => false,
            'message' => $json['message'] ?? null,
            'error' => $json['error'] ?? null,
        ];
    }

    /* ================= ThaiBulkSMS direct (v2) =================
    * Docs:
    * - POST https://otp.thaibulksms.com/v2/otp/request
    * - POST https://otp.thaibulksms.com/v2/otp/verify
    * Auth: HTTP Basic (api-key as username, api-secret as password).
    * Request fields (common v2 usage):
    *   - request: { msisdn: "66xxxxxxxxx", template?, pin_length?, pin_time_to_live?, pin_attempts? }
    *   - verify:  { token: "<token-from-request>", pin: "<user-entered-code>" }
    */

    private function requestViaThaiBulkSMS(string $msisdn, array $meta): array {
        // allow overriding base via option, default kept in constructor
        $url = rtrim($this->apiBase ?: 'https://otp.thaibulksms.com', '/') . '/v2/otp/request';

        // Build request body. You can map/whitelist meta -> OTP options here if needed.
        $body = [
            'key' => $this->apiKey,
            'secret' => $this->apiSecret,
            'msisdn' => $msisdn,
        ];

        // Remove nulls
        $body = array_filter($body, static fn($v) => $v !== null);

        $args = [
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type'  => 'application/json',
                // HTTP Basic with key:secret
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            ],
            'body'    => wp_json_encode($body),
        ];

        $res = wp_remote_post($url, $args);
        if (is_wp_error($res)) {
            return ['ok' => false, 'error' => $res->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($res);
        $raw  = wp_remote_retrieve_body($res);
        $json = json_decode($raw, true) ?: [];

        // v2 usually returns a token; keep refno as fallback for older variants
        $token = $json['token'] ?? null;
        $refno = $json['refno']  ?? null;

        if ($code >= 200 && $code < 300 && ($token || $refno)) {
            return [
                'ok'       => true,
                'token'    => $token,
                'refno'    => $refno,
                'cooldown' => (int) ($json['cooldown'] ?? 60),
            ];
        }

        $err = $json['message'] ?? $json['error'] ?? "SERVER: OTP request failed ({$code})";
        return ['ok' => false, 'error' => $err];
    }

    private function verifyViaThaiBulkSMS(string $token, string $pin): array {
        $url = rtrim($this->apiBase ?: 'https://otp.thaibulksms.com', '/') . '/v2/otp/verify';

        $body = [
            'key' => $this->apiKey,
            'secret' => $this->apiSecret,
            'token' => $token,   // v2 uses "token"
            'pin'   => $pin,    // v2 uses "pin"
        ];

        $args = [
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            ],
            'body'    => wp_json_encode($body),
        ];

        $res = wp_remote_post($url, $args);
        if (is_wp_error($res)) {
            return ['ok' => false, 'error' => $res->get_error_message()];
        }

        $codeHttp = wp_remote_retrieve_response_code($res);
        $raw      = wp_remote_retrieve_body($res);
        $json     = json_decode($raw, true) ?: [];

        // Typical success: status/message flags; keep permissive check
        $okFlag = !empty($json['ok']) || (isset($json['status']) && $json['status'] === 'success');
        if ($codeHttp >= 200 && $codeHttp < 300 && $okFlag) {
            return ['ok' => true, 'message' => $json['message'] ?? null];
        }

        $err = $json['message'] ?? $json['error'] ?? "OTP verify failed ({$codeHttp})";
        return ['ok' => false, 'error' => $err];
    }

}
