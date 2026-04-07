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
        $vcard .= "TEL;TYPE=CELL:" . get_post_meta($profile_id, '_saas_phone', true) . "\n";
        $vcard .= "EMAIL;TYPE=INTERNET:" . get_the_author_meta('user_email', $profile->post_author) . "\n";
        $vcard .= "URL:" . home_url('/' . $profile->post_name) . "\n";
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

/**
 * Conditional Routing Helper
 */
function saas_get_effective_url( $block_id, $default_url ) {
    $rules = get_post_meta( $block_id, '_saas_routing_rules', true ) ?: [];

    // 1. Device-based routing
    if ( ! empty($rules['device_mapping']) ) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if ( stripos($user_agent, 'mobile') !== false && isset($rules['device_mapping']['mobile']) ) {
            return $rules['device_mapping']['mobile'];
        }
    }

    // 2. Geo-based routing (Simulated stub)
    // In production, would use a GeoIP provider
    if ( ! empty($rules['geo_mapping']) ) {
        $country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'US'; // Cloudflare example
        if ( isset($rules['geo_mapping'][$country]) ) {
            return $rules['geo_mapping'][$country];
        }
    }

    return $default_url;
}

// Custom query to find profile by slug
function saas_get_profile_by_slug( $slug ) {
    $posts = get_posts([
        'name'        => $slug,
        'post_type'   => 'saas_profile',
        'post_status' => 'publish',
        'numberposts' => 1
    ]);
    return $posts ? $posts[0] : null;
}

/**
 * License Validation Helper
 */
function saas_is_profile_licensed( $profile_id ) {
    $license_key = get_post_meta( $profile_id, '_saas_license_key', true );
    if ( ! $license_key ) return false;

    // Verify if license key exists in saas_license CPT
    $licenses = get_posts([
        'post_type'  => 'saas_license',
        'title'      => $license_key,
        'post_status' => 'publish',
        'numberposts' => 1
    ]);

    return ! empty($licenses);
}
