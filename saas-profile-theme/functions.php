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

/**
 * Customizer Enhancements for Global Branding
 */
function saas_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'saas_branding_section' , [
        'title'      => 'SaaS Global Branding',
        'priority'   => 30,
    ]);

    $wp_customize->add_setting( 'saas_primary_color' , [
        'default'   => '#6c5ce7',
        'transport' => 'refresh',
    ]);
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'saas_primary_color', [
        'label'      => 'Global Primary Color',
        'section'    => 'saas_branding_section',
        'settings'   => 'saas_primary_color',
    ]));

    $wp_customize->add_setting( 'saas_accent_color' , [
        'default'   => '#39e09b',
        'transport' => 'refresh',
    ]);
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'saas_accent_color', [
        'label'      => 'Global Accent Color',
        'section'    => 'saas_branding_section',
        'settings'   => 'saas_accent_color',
    ]));
}
add_action( 'customize_register', 'saas_customize_register' );

/**
 * Output Customizer CSS
 */
function saas_customizer_css() {
    ?>
    <style type="text/css">
        :root {
            --primary-color: <?php echo get_theme_mod( 'saas_primary_color', '#6c5ce7' ); ?>;
            --accent-color: <?php echo get_theme_mod( 'saas_accent_color', '#39e09b' ); ?>;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'saas_customizer_css' );
