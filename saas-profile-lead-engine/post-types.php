<?php
/**
 * Post Type Registration for SaaS System
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Include Admin Settings
require_once( plugin_dir_path( __FILE__ ) . 'admin-settings.php' );

function saas_register_post_types() {
    // 1. Profiles CPT
    register_post_type( 'saas_profile', [
        'labels' => [
            'name' => 'Profiles',
            'singular_name' => 'Profile',
        ],
        'public' => true,
        'has_archive' => false,
        'rewrite' => false, // We'll handle custom routing for top-level slugs
        'supports' => [ 'title', 'editor', 'thumbnail', 'author', 'revisions' ],
        'show_in_rest' => true,
    ]);

    // 2. Links CPT
    register_post_type( 'saas_link', [
        'labels' => [
            'name' => 'Links',
            'singular_name' => 'Link',
        ],
        'public' => false,
        'show_ui' => true,
        'supports' => [ 'title', 'author' ],
        'show_in_rest' => true,
    ]);

    // 3. Leads CPT
    register_post_type( 'saas_lead', [
        'labels' => [
            'name' => 'Leads',
            'singular_name' => 'Lead',
        ],
        'public' => false,
        'show_ui' => true,
        'supports' => [ 'title', 'author' ],
        'show_in_rest' => true,
    ]);

    // 4. Licenses CPT
    register_post_type( 'saas_license', [
        'labels' => [
            'name' => 'Licenses',
            'singular_name' => 'License',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-id-alt',
        'supports' => [ 'title', 'editor' ],
        'show_in_rest' => true,
    ]);
}
add_action( 'init', 'saas_register_post_types' );

/**
 * Custom Routing for Top-Level Slugs (yourdomain.com/username)
 */
function saas_add_rewrite_rules() {
    add_rewrite_rule(
        '^([^/]+)/?$',
        'index.php?saas_profile=$matches[1]',
        'top'
    );
}
add_action( 'init', 'saas_add_rewrite_rules' );

function saas_query_vars( $vars ) {
    $vars[] = 'saas_profile';
    return $vars;
}
add_filter( 'query_vars', 'saas_query_vars' );

// Load the profile theme if the query var is set
function saas_template_redirect( $template ) {
    $profile_slug = get_query_var( 'saas_profile' );

    if ( $profile_slug ) {
        // Find if a profile with this slug exists
        $profile = get_posts([
            'name'        => $profile_slug,
            'post_type'   => 'saas_profile',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);

        if ( ! empty($profile) ) {
            $custom_template = get_theme_root() . '/saas-profile-theme/index.php';
            if ( file_exists($custom_template) ) {
                return $custom_template;
            }
            // Fallback if the folder is renamed or doesn't exist
            $fallback = plugin_dir_path(__DIR__) . 'saas-profile-theme/index.php';
            if ( file_exists($fallback) ) return $fallback;
        }
    }
    return $template;
}
add_filter( 'template_include', 'saas_template_redirect' );

/**
 * Meta Field Helpers
 */
function saas_get_profile_meta( $profile_id ) {
    return [
        'bio'          => get_post_meta( $profile_id, '_saas_bio', true ),
        'headline'     => get_post_meta( $profile_id, '_saas_headline', true ),
        'theme_color'  => get_post_meta( $profile_id, '_saas_theme_color', true ) ?: '#0073aa',
        'social_links' => get_post_meta( $profile_id, '_saas_social_links', true ) ?: [],
    ];
}

function saas_update_profile_meta( $profile_id, $data ) {
    if ( isset( $data['bio'] ) ) update_post_meta( $profile_id, '_saas_bio', sanitize_textarea_field( $data['bio'] ) );
    if ( isset( $data['headline'] ) ) update_post_meta( $profile_id, '_saas_headline', sanitize_text_field( $data['headline'] ) );
    if ( isset( $data['theme_color'] ) ) update_post_meta( $profile_id, '_saas_theme_color', sanitize_hex_color( $data['theme_color'] ) );
    if ( isset( $data['social_links'] ) ) update_post_meta( $profile_id, '_saas_social_links', $data['social_links'] );
}
