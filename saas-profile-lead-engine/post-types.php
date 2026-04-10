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
        'supports' => [ 'title', 'editor', 'thumbnail', 'author', 'revisions', 'page-attributes' ],
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
        'supports' => [ 'title', 'author', 'page-attributes' ],
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

    // 5. Orders CPT (Revenue Tracking)
    register_post_type( 'saas_order', [
        'labels' => [
            'name' => 'Orders',
            'singular_name' => 'Order',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-cart',
        'supports' => [ 'title', 'author' ],
        'show_in_rest' => true,
    ]);
}
add_action( 'init', 'saas_register_post_types' );

/**
 * Data Isolation: Ensure users only see their own data
 */
function saas_enforce_data_isolation( $query ) {
    if ( is_admin() && ! current_user_can( 'manage_options' ) && $query->is_main_query() ) {
        $post_types = ['saas_profile', 'saas_link', 'saas_lead', 'saas_order'];
        if ( in_array( $query->get( 'post_type' ), $post_types ) ) {
            $query->set( 'author', get_current_user_id() );
        }
    }
}
add_action( 'pre_get_posts', 'saas_enforce_data_isolation' );

/**
 * Custom Routing for Top-Level Slugs (yourdomain.com/username)
 * Using a more resilient approach to avoid hijacking homepage/admin/existing pages.
 */
function saas_add_rewrite_rules() {
    // Only apply if it's not a standard WP path
    add_rewrite_rule(
        '^([^/]+)/?$',
        'index.php?saas_profile=$matches[1]',
        'bottom' // Move to bottom to let existing pages/posts take precedence
    );
}
add_action( 'init', 'saas_add_rewrite_rules' );

// One-time flush for development environment
function saas_flush_rules_once() {
    if ( ! get_option( 'saas_rules_flushed_v3' ) ) {
        saas_add_rewrite_rules();
        flush_rewrite_rules();
        update_option( 'saas_rules_flushed_v3', true );
    }
}
add_action( 'init', 'saas_flush_rules_once', 20 );

function saas_query_vars( $vars ) {
    $vars[] = 'saas_profile';
    return $vars;
}
add_filter( 'query_vars', 'saas_query_vars' );

// Load the profile theme if the query var is set
function saas_template_redirect( $template ) {
    // Never hijack admin
    if ( is_admin() ) return $template;

    // 1. Resolve via Custom Domain (Elite Feature)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ( $host && $host !== parse_url(home_url(), PHP_URL_HOST) ) {
        $profile_by_domain = get_posts([
            'post_type' => 'saas_profile',
            'meta_key' => '_saas_custom_domain',
            'meta_value' => $host,
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
        if ( ! empty($profile_by_domain) ) {
            set_query_var( 'saas_profile', $profile_by_domain[0]->post_name );
            $custom_template = get_theme_root() . '/saas-profile-theme/index.php';
            if ( file_exists($custom_template) ) return $custom_template;
        }
    }

    $profile_slug = get_query_var( 'saas_profile' );

    // Check if it's actually a profile and not a standard page/post
    if ( $profile_slug && ! is_singular(['page', 'post']) ) {
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
        ];
    }
}

if ( ! function_exists( 'saas_update_profile_meta' ) ) {
    function saas_update_profile_meta( $profile_id, $data ) {
        if ( isset( $data['bio'] ) ) update_post_meta( $profile_id, '_saas_bio', sanitize_textarea_field( $data['bio'] ) );
        if ( isset( $data['headline'] ) ) update_post_meta( $profile_id, '_saas_headline', sanitize_text_field( $data['headline'] ) );
        if ( isset( $data['theme_color'] ) ) update_post_meta( $profile_id, '_saas_theme_color', sanitize_hex_color( $data['theme_color'] ) );
        if ( isset( $data['social_links'] ) ) update_post_meta( $profile_id, '_saas_social_links', $data['social_links'] );
    }
}
