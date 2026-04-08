<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    $slug = get_query_var( 'saas_profile' );
    if ($slug) :
        $profile = saas_get_profile_by_slug($slug);
        if ($profile) :
            $p_id = $profile->ID;
            $p_meta = saas_get_profile_meta($p_id);
            $custom_title = get_post_meta($p_id, '_saas_seo_title', true);
            $custom_desc = get_post_meta($p_id, '_saas_seo_desc', true);
            $custom_favicon = get_post_meta($p_id, '_saas_favicon', true);
            ?>
            <title><?php echo esc_html($custom_title ?: $profile->post_title . ' | Digital Business Card'); ?></title>
            <meta name="description" content="<?php echo esc_attr($custom_desc ?: wp_trim_words($p_meta['bio'], 25)); ?>">
            <?php if($custom_favicon) : ?><link rel="icon" href="<?php echo esc_url($custom_favicon); ?>"><?php endif; ?>
            <meta property="og:title" content="<?php echo esc_html($profile->post_title); ?>">
            <meta property="og:description" content="<?php echo esc_attr($p_meta['headline']); ?>">
            <meta property="og:type" content="profile">
            <meta property="og:url" content="<?php echo home_url('/' . $slug); ?>">
            <link rel="canonical" href="<?php echo home_url('/' . $slug); ?>">
            <?php
            $og_image = get_the_post_thumbnail_url($profile->ID, 'full');
            if (!$og_image) {
                $cover_id = get_post_meta($profile->ID, '_saas_cover_id', true);
                if ($cover_id) $og_image = wp_get_attachment_url($cover_id);
            }
            if ($og_image) : ?>
                <meta property="og:image" content="<?php echo esc_url($og_image); ?>">
                <meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
            <?php endif; ?>
        <?php endif;
    endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@400;700;900&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <?php
    if ($slug) {
        $profile = saas_get_profile_by_slug($slug);
        if ($profile) {
            $payments = new Saas_Payments();
            if ($payments->is_pro_user($profile->post_author)) {
                echo get_post_meta($profile->ID, '_saas_header_scripts', true);
            }
        }
    }
    ?>
</head>
<body <?php body_class( $theme_class ?? '' ); ?> data-saas-theme="light">

<?php
$global_logo = get_option('saas_global_logo');
if ($global_logo) : ?>
    <div class="saas-global-header" style="text-align:center; padding:20px 0;">
        <img src="<?php echo esc_url($global_logo); ?>" alt="SaaS Logo" style="max-height:40px;">
    </div>
<?php endif; ?>

<header id="masthead" class="site-header" style="padding: 20px 0; background: #fff; border-bottom: 1px solid #eee;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px;">
        <div class="site-branding">
            <?php
            if ( has_custom_logo() ) {
                the_custom_logo();
            } else {
                echo '<a href="' . esc_url( home_url( '/' ) ) . '" style="font-size: 1.5rem; font-weight: 800; text-decoration: none; color: #333;">' . get_bloginfo( 'name' ) . '</a>';
            }
            ?>
        </div>
        <nav id="site-navigation" class="main-navigation">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'container'      => false,
                'menu_class'     => 'primary-menu-list',
                'fallback_cb'    => false,
            ]);
            ?>
            <style>
                .primary-menu-list { list-style: none; display: flex; gap: 30px; margin: 0; padding: 0; }
                .primary-menu-list a { text-decoration: none; color: #666; font-weight: 600; font-size: 0.95rem; }
                .primary-menu-list a:hover { color: var(--primary-color, #6c5ce7); }
            </style>
        </nav>
        <div class="header-cta">
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo home_url('/dashboard'); ?>" class="button button-primary" style="background: var(--primary-color, #6c5ce7); color: #fff; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: 700;">Dashboard</a>
            <?php else : ?>
                <a href="<?php echo home_url('/login'); ?>" style="text-decoration: none; color: #666; font-weight: 600; margin-right: 20px;">Login</a>
                <a href="<?php echo home_url('/register'); ?>" class="button button-primary" style="background: var(--primary-color, #6c5ce7); color: #fff; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: 700;">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php wp_body_open(); ?>
