<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
