<?php
/**
 * The front page template file.
 */

get_header(); ?>

<?php
$h_title = get_option('saas_home_title') ?: 'Launch your digital identity in 60 seconds.';
$h_hero  = get_option('saas_home_hero') ?: 'Combine your link-in-bio, business card, and lead magnets into one high-performance page.';
$h_cta   = get_option('saas_home_cta') ?: 'Get Started Free';
$h_img   = get_option('saas_home_image');
?>

<main id="front-page" class="site-main" style="min-height: 100vh; display: flex; flex-direction:column; align-items: center; background: #fff; position:relative; overflow:hidden;">
    <!-- Animated Mesh Background -->
    <div style="position:absolute; top:0; left:0; width:100%; height:100%; z-index:0; opacity:0.1;">
        <div style="position:absolute; width:150%; height:150%; background:radial-gradient(circle, #6e45e2 0%, transparent 60%); top:-25%; left:-25%; animation: rotate 30s linear infinite;"></div>
        <div style="position:absolute; width:150%; height:150%; background:radial-gradient(circle, #39e09b 0%, transparent 60%); bottom:-25%; right:-25%; animation: rotate 20s linear reverse infinite;"></div>
    </div>

    <div class="landing-content" style="max-width: 1200px; text-align: center; z-index: 1; padding: 120px 20px;">
        <h1 style="font-size: 5.5rem; font-weight: 900; line-height: 1; margin-bottom:30px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -3px; transform: scale(1);">
            <?php echo esc_html($h_title); ?>
        </h1>
        <p style="font-size: 1.8rem; color: #636e72; font-weight: 500; margin-bottom: 50px; max-width: 800px; margin-left: auto; margin-right: auto; line-height: 1.4;">
            <?php echo esc_html($h_hero); ?>
        </p>

        <div class="cta-actions">
            <a href="<?php echo home_url('/register'); ?>" class="saas-cta-btn-vibrant">
                <?php echo esc_html($h_cta); ?>
            </a>
            <p style="margin-top: 20px; color: #a0a0a0; font-size: 0.9rem;">No credit card required. Setup in minutes.</p>
        </div>

        <?php if ($h_img) : ?>
            <div class="hero-image-container" style="margin-top: 80px; transform: perspective(2000px) rotateX(10deg) translateY(-20px);">
                <img src="<?php echo esc_url($h_img); ?>" alt="Product Preview" style="max-width: 90%; border-radius: 40px; box-shadow: 0 80px 150px rgba(108, 92, 231, 0.3);">
            </div>
        <?php else : ?>
            <!-- Default Dashboard Preview Mockup -->
            <div class="hero-image-container" style="margin-top: 80px; transform: perspective(2000px) rotateX(10deg) translateY(-20px); max-width: 1000px; margin-left: auto; margin-right: auto;">
                <div style="background: #fff; border-radius: 40px; box-shadow: 0 80px 150px rgba(108, 92, 231, 0.2); padding: 40px; border: 1px solid #eee; display: flex; gap: 30px; text-align: left;">
                    <div style="flex: 1; background: #f8f9fa; border-radius: 20px; padding: 20px;">
                        <div style="width: 40px; height: 10px; background: #ddd; margin-bottom: 20px;"></div>
                        <div style="width: 100%; height: 200px; background: #fff; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);"></div>
                        <div style="width: 80%; height: 10px; background: #ddd;"></div>
                    </div>
                    <div style="flex: 2;">
                        <div style="height: 40px; background: #6c5ce7; border-radius: 10px; margin-bottom: 20px; width: 60%;"></div>
                        <div style="height: 15px; background: #eee; border-radius: 5px; margin-bottom: 10px;"></div>
                        <div style="height: 15px; background: #eee; border-radius: 5px; margin-bottom: 10px; width: 80%;"></div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 40px;">
                            <div style="height: 80px; background: #f8f9fa; border-radius: 15px;"></div>
                            <div style="height: 80px; background: #f8f9fa; border-radius: 15px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Unified Conversion Sections (Reuse Landing Parts) -->
<?php include __DIR__ . '/template-parts/content-hero.php'; ?>

<style>
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>

<?php get_footer(); ?>
