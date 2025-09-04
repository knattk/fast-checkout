<?php
namespace FastCheckout\Rest;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) exit;

abstract class Abstract_Controller {

    protected string $namespace = 'fc/v1';

    // ให้คลาสลูก implement เมธอดนี้
    abstract public function register_routes(): void;

    /* ========== JSON helpers ========== */
    protected function json_ok(array $data = [], int $status = 200): WP_REST_Response {
        return new WP_REST_Response(array_merge(['ok' => true], $data), $status);
    }

    protected function json_error(string $code, string $message, int $status = 400, array $extra = []): WP_REST_Response {
        return new WP_REST_Response(array_merge(['ok' => false, 'code' => $code, 'message' => $message], $extra), $status);
    }

    /* ========== Permission / Security ========== */

    public function permission_public(WP_REST_Request $r) {
        // check Origin/Referer
        if (!$this->check_origin($r)) {
            return new \WP_Error('fc_bad_origin', __('Invalid origin or referer', 'fastcheckout'), ['status' => 403]);
        }

        // logged in -> check WP nonce from header
        if (is_user_logged_in()) {
            $nonce = $r->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new \WP_Error('fc_nonce_fail', __('Nonce failed (logged-in).', 'fastcheckout'), ['status' => 403]);
            }
            return true;
        }

        // not logged in  -> check CSRF nonce from body/params or header
        $csrf = $r->get_param('csrf') ?: $r->get_header('x-fc-csrf');
        if (!$csrf || !wp_verify_nonce($csrf, 'fc_public')) {
            return new \WP_Error('fc_csrf_fail', __('CSRF invalid (public).', 'fastcheckout'), ['status' => 403]);
        }
        return true;
    }

    // check Origin and Referer headers (ถ้ามี) ว่ามาจากโดเมนเดียวกับเว็บไหม
    protected function check_origin(WP_REST_Request $r): bool {
        $site   = wp_parse_url(home_url());
        $origin = $r->get_header('origin');
        $refer  = $r->get_header('referer');

        $ok = true;
        if ($origin) {
            $o = wp_parse_url($origin);
            $ok = $ok && $o && strtolower($o['host'] ?? '') === strtolower($site['host'] ?? '');
        }
        if ($refer) {
            $re = wp_parse_url($refer);
            $ok = $ok && $re && strtolower($re['host'] ?? '') === strtolower($site['host'] ?? '');
        }
        return $ok;
    }

    public function sanitize_phone($v): string {
        return true;
        return preg_replace('/\D+/', '', (string)$v);
    }
    public function validate_phone($v): bool {
        return true;
        $len = strlen((string)$v);
        return ($len >= 9 && $len <= 11);
    }

    protected function mint_otp_token(string $subject, int $ttl = 300): string {
        $token = 'ot_' . wp_generate_password(24, false, false);
        set_transient('fc_otptok_' . md5($token), ['subject' => $subject, 'issued' => time()], $ttl);
        return $token;
    }

    protected function consume_otp_token(string $token): ?string {
        $k = 'fc_otptok_' . md5($token);
        $data = get_transient($k);
        if (!$data || !is_array($data)) return null;
        delete_transient($k); // one-time use
        return $data['subject'] ?? null;
    }
}
