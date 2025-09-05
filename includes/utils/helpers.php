<?php
namespace FastCheckout\Utils;

/**
 * Existing helpers
 */
function fc_encrypt( $data ) {
    if ( empty( $data ) ) return '';

    $key = hash( 'sha256', AUTH_KEY );
    $iv  = substr( hash( 'sha256', SECURE_AUTH_KEY ), 0, 16 );

    $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
    return base64_encode( $encrypted );
}

function fc_decrypt( $data ) {
    if ( empty( $data ) ) return '';

    $key = hash( 'sha256', AUTH_KEY );
    $iv  = substr( hash( 'sha256', SECURE_AUTH_KEY ), 0, 16 );

    $decrypted = openssl_decrypt( base64_decode( $data ), 'AES-256-CBC', $key, 0, $iv );
    return $decrypted;
}



/**
 * Returns an esc_attr()-safe decrypted value if possible,
 * or the original string if it doesn't decrypt cleanly.
 */
function maybe_decrypt( $encrypted ) {
    $decrypted = '';
    if ( ! empty( $encrypted ) ) {
        $decrypted = fc_decrypt( $encrypted );
        if ( $decrypted === false || $decrypted === null ) {
            $decrypted = $encrypted;
        }
    }
    return esc_attr( $decrypted );
}

/**
 * Wrapper for use as a sanitize_callback in register_setting.
 * Encrypts non-empty, non-already-encrypted values.
 */
function encrypted( $value ) {
    return encrypt_value( $value );
}

function encrypt_value( $value ) {
    $clean = sanitize_text_field( $value );
    if ( empty( $clean ) ) {
        return '';
    }
    if ( is_encrypted( $clean ) ) {
        return $clean;
    }
    return fc_encrypt( $clean );
}

function is_encrypted( $value ) {
    $decoded = base64_decode( $value, true );
    return $decoded !== false && strlen( $decoded ) > 16;
}


/**
 * IP address list sanitizer for settings field
 */
function sanitize_ip_list( $value ) {
    $ips = array_map( 'trim', explode( ',', (string) $value ) );
    $valid_ips = array_filter( $ips, function( $ip ) {
        return filter_var( $ip, FILTER_VALIDATE_IP );
    } );
    return implode( ', ', $valid_ips );
}