<?php
/**
 * Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function saas_theme_scripts() {
    wp_enqueue_style( 'saas-main-style', get_stylesheet_uri() );
    wp_enqueue_script( 'jquery' ); // Ensure jQuery is loaded

    // Pass AJAX and REST URLs to theme
    wp_localize_script( 'jquery', 'saas_data', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'rest_url' => get_rest_url( null, '/saas/v1' )
    ]);
}
add_action( 'wp_enqueue_scripts', 'saas_theme_scripts' );

// Add support for background customization
add_theme_support( 'custom-background' );

// Add support for theme logo
add_theme_support( 'custom-logo' );
