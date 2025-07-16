<?php

namespace FastCheckout;

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
    return $decrypted; // no base64_decode here
}
