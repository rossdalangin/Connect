<?php
/**
 * Template Name: Story Card Download View
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$profile_id = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
$profile = get_post($profile_id);

if (!$profile || $profile->post_type !== 'saas_profile') {
    wp_die('Invalid profile');
}

$meta = saas_get_profile_meta($profile_id);
$theme_color = get_post_meta($profile_id, '_saas_theme_color', true) ?: '#6c5ce7';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Card - <?php echo esc_html($profile->post_title); ?></title>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; font-family: 'Inter', sans-serif; overflow: hidden; }
        .story-card {
            width: 1080px;
            height: 1920px;
            background: linear-gradient(135deg, <?php echo $theme_color; ?> 0%, #000 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px;
            box-sizing: border-box;
            position: relative;
        }
        .avatar {
            width: 350px;
            height: 350px;
            border-radius: 50%;
            border: 15px solid #fff;
            margin-bottom: 50px;
            object-fit: cover;
        }
        h1 { font-size: 8rem; margin: 0 0 20px; font-weight: 900; }
        p { font-size: 3rem; opacity: 0.8; margin: 0 0 100px; text-align: center; }
        .qr-wrapper {
            background: #fff;
            padding: 40px;
            border-radius: 40px;
            margin-bottom: 40px;
        }
        .qr-wrapper img { width: 400px; }
        .footer-text { font-size: 2.5rem; font-weight: 800; text-transform: uppercase; letter-spacing: 5px; }
    </style>
</head>
<body>
    <div class="story-card">
        <?php if ( has_post_thumbnail( $profile_id ) ) : ?>
            <?php echo get_the_post_thumbnail( $profile_id, 'thumbnail', ['class' => 'avatar']); ?>
        <?php endif; ?>
        <h1><?php echo esc_html($profile->post_title); ?></h1>
        <p><?php echo esc_html($meta['headline']); ?></p>

        <div class="qr-wrapper">
            <img src="<?php echo saas_get_profile_qr_url($profile->post_name, '#000000'); ?>">
        </div>
        <div class="footer-text">Scan to Connect</div>
    </div>
</body>
</html>
