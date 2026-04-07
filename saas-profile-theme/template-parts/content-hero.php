<?php
/**
 * Hero Section Template Part
 */
$h_title = get_option('saas_home_title') ?: get_the_title();
$h_hero  = get_option('saas_home_hero');
$h_cta   = get_option('saas_home_cta') ?: 'Get Started Free';
$h_img   = get_option('saas_home_image');
?>
<section class="hero-vibrant" style="padding: 120px 20px; text-align: center; background: #fff; position: relative; overflow: hidden;">
    <div class="hero-bg-accent"></div>
    <div class="container" style="max-width: 900px; margin: 0 auto; position: relative; z-index: 1;">
        <h1 class="hero-title"><?php echo esc_html($h_title); ?></h1>
        <?php if ($h_hero) : ?>
            <p class="hero-subtitle"><?php echo esc_html($h_hero); ?></p>
        <?php endif; ?>

        <div class="trusted-by">
            <p>Trusted by innovators at</p>
            <div class="logo-grid">
                <?php
                $logos = json_decode(get_option('saas_home_trusted_logos'), true) ?: [
                    'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg',
                    'https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg',
                    'https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg'
                ];
                foreach ($logos as $logo) : ?>
                    <img src="<?php echo esc_url($logo); ?>" alt="Partner Logo">
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($h_img) : ?>
            <div class="hero-mockup">
                <img src="<?php echo esc_url($h_img); ?>" alt="SaaS Mockup">
            </div>
        <?php endif; ?>

        <div class="hero-actions">
            <a href="<?php echo home_url('/register'); ?>" class="btn-vibrant-cta"><?php echo esc_html($h_cta); ?></a>
        </div>
    </div>
</section>
