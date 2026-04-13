<?php
/**
 * Utility Features: vCard & QR Code
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Handle vCard Download
add_action( 'init', 'saas_handle_vcard_download' );
function saas_handle_vcard_download() {
    if ( isset( $_GET['saas_action'] ) && $_GET['saas_action'] === 'vcard' ) {
        $profile_param = $_GET['profile'] ?? '';
        $profile = null;

        if ( is_numeric($profile_param) ) {
            $profile = get_post( intval($profile_param) );
        } elseif ( !empty($profile_param) ) {
            $profile = saas_get_profile_by_slug( sanitize_title($profile_param) );
        }

        if ( ! $profile || $profile->post_type !== 'saas_profile' ) return;

        $profile_id = $profile->ID;
        $meta = saas_get_profile_meta( $profile_id );

        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "FN:" . $profile->post_title . "\n";
        $vcard .= "ORG:" . (get_post_meta($profile_id, '_saas_company', true) ?: '') . "\n";
        $vcard .= "TITLE:" . $meta['headline'] . "\n";
        $vcard .= "TEL;TYPE=CELL:" . ($meta['phone'] ?: '') . "\n";
        $vcard .= "EMAIL;TYPE=INTERNET:" . get_the_author_meta('user_email', $profile->post_author) . "\n";
        $vcard .= "URL:" . home_url('/' . $profile->post_name) . "\n";

        // Photo integration (Base64)
        $avatar_id = $meta['avatar_id'];
        if ( $avatar_id ) {
            $path = get_attached_file( $avatar_id );
            if ( $path && file_exists($path) ) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $vcard .= "PHOTO;TYPE=" . strtoupper($type) . ";ENCODING=B:" . base64_encode($data) . "\n";
            }
        }

        // Add social links to vCard
        if ( is_array($meta['social_links']) ) {
            foreach ($meta['social_links'] as $platform => $url) {
                $vcard .= "X-SOCIALPROFILE;TYPE=" . strtoupper($platform) . ":" . $url . "\n";
            }
        }

        $vcard .= "NOTE:" . str_replace("\n", "\\n", $meta['bio']) . "\n";
        $vcard .= "END:VCARD";

        header('Content-Type: text/vcard');
        header('Content-Disposition: attachment; filename="' . sanitize_title($profile->post_title) . '.vcf"');
        echo $vcard;
        exit;
    }
}

/**
 * QR Code Generator Utility
 */
function saas_get_profile_qr_url( $profile_slug, $color = '000000' ) {
    $profile_url = home_url( '/' . $profile_slug );
    $color = str_replace('#', '', $color);
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&color=$color&data=" . urlencode($profile_url);
}

/**
 * Data Helpers (Guarded for Theme Compatibility)
 */
if ( ! function_exists( 'saas_get_profile_meta' ) ) {
    function saas_get_profile_meta( $profile_id ) {
        return [
            'bio'          => get_post_meta( $profile_id, '_saas_bio', true ),
            'headline'     => get_post_meta( $profile_id, '_saas_headline', true ),
            'theme_color'  => get_post_meta( $profile_id, '_saas_theme_color', true ) ?: '#6c5ce7',
            'social_links' => get_post_meta( $profile_id, '_saas_social_links', true ) ?: [],
            'phone'        => get_post_meta( $profile_id, '_saas_phone', true ),
            'avatar_id'    => get_post_thumbnail_id( $profile_id ),
            'cover_id'     => get_post_meta( $profile_id, '_saas_cover_id', true ),
            'niche'        => get_post_meta( $profile_id, '_saas_niche', true ),
            'company'      => get_post_meta( $profile_id, '_saas_company', true ),
            'custom_domain'=> get_post_meta( $profile_id, '_saas_custom_domain', true ),
            'bg_type'      => get_post_meta( $profile_id, '_saas_bg_type', true ) ?: 'flat',
            'bg_color'     => get_post_meta( $profile_id, '_saas_bg_color', true ) ?: '#f3f3f1',
            'bg_gradient'  => get_post_meta( $profile_id, '_saas_bg_gradient', true ),
            'btn_shape'    => get_post_meta( $profile_id, '_saas_btn_shape', true ) ?: 'pill',
            'font_family'  => get_post_meta( $profile_id, '_saas_font_family', true ) ?: "'Inter', sans-serif",
            'shadow_style' => get_post_meta( $profile_id, '_saas_container_shadow', true ) ?: 'soft',
            'profile_theme'=> get_post_meta($profile_id, '_saas_profile_theme', true) ?: 'light',
            'custom_css'   => get_post_meta($profile_id, '_saas_custom_css', true),
            'qr_color'     => get_post_meta($profile_id, '_saas_qr_color', true) ?: '#000000',
        ];
    }
}

/**
 * Conditional Routing Helper
 */
if ( ! function_exists( 'saas_get_effective_url' ) ) {
    function saas_get_effective_url( $block_id, $default_url ) {
        // 1. Device-based routing
        $mobile_url = get_post_meta($block_id, '_saas_url_mobile', true);
        if ($mobile_url) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ( stripos($user_agent, 'mobile') !== false ) {
                return $mobile_url;
            }
        }

        // 2. Geo-based routing
        $geo_url = get_post_meta($block_id, '_saas_url_geo', true);
        $target_country = get_post_meta($block_id, '_saas_url_geo_country', true);
        if ($geo_url && $target_country) {
            $visitor_country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'US';
            if ( strtoupper($visitor_country) === strtoupper($target_country) ) {
                return $geo_url;
            }
        }

        return $default_url;
    }
}

/**
 * Custom query to find profile by slug
 */
if ( ! function_exists( 'saas_get_profile_by_slug' ) ) {
    function saas_get_profile_by_slug( $slug ) {
        $posts = get_posts([
            'name'        => $slug,
            'post_type'   => 'saas_profile',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
        return $posts ? $posts[0] : null;
    }
}

/**
 * License Validation Helper
 */
function saas_is_profile_licensed( $profile_id ) {
    $author_id = get_post_field( 'post_author', $profile_id );

    // 1. Check if user is a PRO subscriber
    $payments = new Saas_Payments();
    if ( $payments->is_pro_user( $author_id ) ) {
        return true;
    }

    // 2. Check license key
    $license_key = get_post_meta( $profile_id, '_saas_license_key', true );
    if ( ! empty($license_key) ) {
        $licenses = get_posts([
            'post_type'   => 'saas_license',
            'title'       => $license_key,
            'post_status' => 'publish',
            'numberposts' => 1
        ]);

        if ( ! empty($licenses) ) {
            $expiry = get_post_meta( $licenses[0]->ID, '_saas_license_expiry', true );
            if ( ! $expiry || $expiry > time() ) {
                return true;
            }
        }
    }

    return false;
}
