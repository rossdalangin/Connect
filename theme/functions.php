<?php
/**
 * Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function saas_theme_scripts() {
    wp_enqueue_style( 'saas-main-style', get_stylesheet_uri() );

    // Pass AJAX URL to theme
    wp_localize_script( 'jquery', 'saas_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' )
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
