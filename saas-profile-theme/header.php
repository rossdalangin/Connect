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
            $p_meta = saas_get_profile_meta($profile->ID);
            ?>
            <title><?php echo esc_html($profile->post_title); ?> | Digital Business Card</title>
            <meta name="description" content="<?php echo esc_attr(wp_trim_words($p_meta['bio'], 25)); ?>">
            <meta property="og:title" content="<?php echo esc_html($profile->post_title); ?>">
            <meta property="og:description" content="<?php echo esc_attr($p_meta['headline']); ?>">
            <meta property="og:type" content="profile">
            <meta property="og:url" content="<?php echo home_url('/' . $slug); ?>">
            <?php if (has_post_thumbnail($profile->ID)) : ?>
                <meta property="og:image" content="<?php echo get_the_post_thumbnail_url($profile->ID, 'full'); ?>">
            <?php endif; ?>
        <?php endif;
    endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class( $theme_class ?? '' ); ?> data-saas-theme="light">

<?php
$global_logo = get_option('saas_global_logo');
if ($global_logo) : ?>
    <div class="saas-global-header" style="text-align:center; padding:20px 0;">
        <img src="<?php echo esc_url($global_logo); ?>" alt="SaaS Logo" style="max-height:40px;">
    </div>
<?php endif; ?>

<?php wp_body_open(); ?>
