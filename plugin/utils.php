<?php
/**
 * Utility Features: vCard & QR Code
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Handle vCard Download
add_action( 'init', 'saas_handle_vcard_download' );
function saas_handle_vcard_download() {
    if ( isset( $_GET['saas_action'] ) && $_GET['saas_action'] === 'vcard' && isset( $_GET['profile'] ) ) {
        $profile_id = intval( $_GET['profile'] );
        $profile = get_post( $profile_id );

        if ( ! $profile || $profile->post_type !== 'saas_profile' ) return;

        $meta = saas_get_profile_meta( $profile_id );

        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "FN:" . $profile->post_title . "\n";
        $vcard .= "TITLE:" . $meta['headline'] . "\n";
        $vcard .= "NOTE:" . str_replace("\n", "\\n", $meta['bio']) . "\n";
        $vcard .= "END:VCARD";

        header('Content-Type: text/vcard');
        header('Content-Disposition: attachment; filename="contact.vcf"');
        echo $vcard;
        exit;
    }
}

/**
 * QR Code Generator Stub
 * (In production, would use a library like endroid/qr-code or an API)
 */
function saas_get_profile_qr_url( $profile_slug ) {
    $profile_url = home_url( '/' . $profile_slug );
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($profile_url);
}
