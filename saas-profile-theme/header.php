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
            <?php if (has_post_thumbnail($profile->ID)) : ?>
                <meta property="og:image" content="<?php echo get_the_post_thumbnail_url($profile->ID, 'full'); ?>">
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

<?php wp_body_open(); ?>
