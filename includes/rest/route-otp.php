<?php
namespace FastCheckout\Rest;

use WP_REST_Request;
use FastCheckout\Services\OTP_Service;

if (!defined('ABSPATH')) exit;

/**
 * เส้นทาง OTP:
 * - POST /fc/v1/otp/request
 * - POST /fc/v1/otp/verify
 */
class Route_OTP extends Abstract_Controller {
    private OTP_Service $otp;
    private int $otp_token_ttl = 300; // 5 นาที

    public function __construct(OTP_Service $otp) {
        $this->otp = $otp;
    }

    public function register_routes(): void {
        register_rest_route($this->namespace, '/otp/request', [
            'methods'  => 'POST',
            'callback' => [$this, 'otp_request'],
            'permission_callback' => [$this, 'permission_public'],
            'args' => [
                'msisdn' => [
                    'required' => true,
                    'sanitize_callback' => [$this, 'sanitize_phone'],
                    'validate_callback' => [$this, 'validate_phone'],
                ],
                'client_fingerprint' => ['required' => false],
                'csrf' => ['required' => false],
            ],
        ]);

        register_rest_route($this->namespace, '/otp/verify', [
            'methods'  => 'POST',
            'callback' => [$this, 'otp_verify'],
            'permission_callback' => [$this, 'permission_public'],
            'args' => [
                'token' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'pin'   => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
                'csrf'   => ['required' => false],
            ],
        ]);
    }

    public function otp_request(WP_REST_Request $r) {
        // (อาจเพิ่ม rate-limit ที่นี่ด้วย transient ต่อ IP)


        $msisdn  = $r->get_param('msisdn');
        $finger = sanitize_text_field((string)$r->get_param('client_fingerprint'));

        // $res = $this->otp->requestOTP($msisdn, ['fp' => $finger]);
        $res = $this->otp->requestOTP($msisdn);
        if (empty($res['ok'])) {
            return $this->json_error('OTP_REQUEST_FAILED', __('ROUTE: OTP request failed', 'fastcheckout'), 502, [
                'res' => $res,
                'detail' => $res['error'] ?? null,
            ]);
        }

        // เก็บไว้ตรวจสอบตอน verify 10 นาที
        if (!empty($res['token'])) {
            set_transient('fc_otpreq_' . md5($res['token']), [
                'msisdn' => $msisdn,
                'refno'  => $res['refno'] ?? null,
                'ts'     => time(),
            ], 600);
        }

        return $this->json_ok([
            'status' => 'success',
            'refno'   => $res['refno'] ?? '',
            'token'   => $res['token'] ?? '',
            'pin'     => $res['pin'] ?? null, // for test/dev only
            'cooldown' => $res['cooldown'] ?? 60,
        ]);
    }

    public function otp_verify(WP_REST_Request $r) {
        $token  = $r->get_param('token');
        $pin = $r->get_param('pin');

        $res = $this->otp->verifyOTP($token, $pin);
        if (empty($res['ok'])) {
            return $this->json_error('401', __('รหัสยืนยัน OTP ไม่ถูกต้อง', 'fastcheckout'), 400, []);
        }

        // ดึง fc_otpreq_xxx ที่เคยเก็บไว้ตอน request ออกมา
        $map = get_transient('fc_otpreq_' . md5($token)) ?: [];
        $subject = $map['msisdn'] ?? ('ref:' . ($map['refno'] ?? substr(hash('sha256',(string)$token),0,16)));
        delete_transient('fc_otpreq_' . md5($token)); // เคลียร์ mapping ทิ้ง

        $subject   = $res['msisdn'] ?? ('ref:' . $ref);
        $otp_token = $this->mint_otp_token($subject, $this->otp_token_ttl);

        return $this->json_ok([
            'status' => 'success',
            'otp_token'  => $otp_token,
            'expires_in' => $this->otp_token_ttl,
        ]);
    }

}
