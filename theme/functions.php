<?php
/**
 * Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function saas_theme_scripts() {
    wp_enqueue_style( 'saas-main-style', get_stylesheet_uri() );

    // Pass AJAX and REST URLs to theme
    wp_localize_script( 'jquery', 'saas_data', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'rest_url' => get_rest_url( null, '/saas/v1' )
    ]);
}
add_action( 'wp_enqueue_scripts', 'saas_theme_scripts' );

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
