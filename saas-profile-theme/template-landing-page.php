<?php
/**
 * Template Name: Landing Page (Clean)
 */

get_header(); ?>

<?php
$h_title = get_option('saas_home_title') ?: get_the_title();
$h_hero  = get_option('saas_home_hero');
$h_cta   = get_option('saas_home_cta') ?: 'Get Started Free';
$h_img   = get_option('saas_home_image');
?>
<main id="landing-page" class="site-main" style="min-height: 100vh; display: flex; flex-direction:column; align-items: center; justify-content: center; padding: 120px 20px; background: #fff; position:relative; overflow:hidden;">
    <!-- Animated Gradient Background -->
    <div style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:0; opacity:0.1;">
        <div style="position:absolute; width:150%; height:150%; background:radial-gradient(circle, #6e45e2 0%, transparent 50%); top:-25%; left:-25%; animation: rotate 20s linear infinite;"></div>
    </div>
    <style> @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } </style>

    <div class="landing-content" style="max-width: 800px; text-align: center;">
        <header class="landing-header" style="margin-bottom: 60px; max-width:900px;">
            <h1 style="font-size: 5rem; font-weight: 900; line-height: 1; margin-bottom:30px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -2px;">
                <?php echo esc_html($h_title); ?>
            </h1>
            <?php if ($h_hero) : ?>
                <p style="font-size: 1.75rem; color: #636e72; font-weight: 500;"><?php echo esc_html($h_hero); ?></p>
            <?php endif; ?>
        </header>

        <?php if ($h_img) : ?>
            <div class="hero-image-container" style="margin: 40px 0; transform: perspective(1000px) rotateX(5deg);">
                <img src="<?php echo esc_url($h_img); ?>" alt="SaaS Preview" style="max-width: 80%; border-radius: 24px; box-shadow: 0 50px 100px rgba(0,0,0,0.1);">
            </div>
        <?php endif; ?>

        <div class="landing-body" style="font-size: 1.25rem; color: #666;">
            <?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
            <a href="<?php echo home_url('/register'); ?>" class="saas-cta-btn-vibrant">
                <?php echo esc_html($h_cta); ?>
            </a>
        </div>
    </div>
</main>

<!-- Features Grid -->
<section class="features-section" style="padding: 100px 20px; background: #fff;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 2.5rem; margin-bottom: 60px;">Everything you need to grow online</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px;">
            <div>
                <div style="font-size: 3rem; margin-bottom: 20px;">🚀</div>
                <h3>Fast Setup</h3>
                <p>Launch your profile in under 60 seconds with our pro templates.</p>
            </div>
            <div>
                <div style="font-size: 3rem; margin-bottom: 20px;">📊</div>
                <h3>Smart Analytics</h3>
                <p>Track every click and view with high-performance tracking.</p>
            </div>
            <div>
                <div style="font-size: 3rem; margin-bottom: 20px;">🎯</div>
                <h3>Lead Capture</h3>
                <p>Convert your social traffic into real customers with built-in forms.</p>
            </div>
        </div>
    </div>
</section>

<style>
    .saas-cta-btn-vibrant {
        display: inline-block;
        margin-top: 40px;
        padding: 20px 48px;
        background: linear-gradient(135deg, #6e45e2 0%, #88d3ce 100%);
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 800;
        font-size: 1.25rem;
        box-shadow: 0 10px 30px rgba(110, 69, 226, 0.4);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .saas-cta-btn-vibrant:hover {
        transform: scale(1.05) translateY(-5px);
    }
</style>

<style>
    .landing-body a {
        display: inline-block;
        margin-top: 24px;
        padding: 16px 32px;
        background: var(--primary-color, #39e09b);
        color: #fff;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(57, 224, 155, 0.3);
    }
</style>

<?php get_footer(); ?>
