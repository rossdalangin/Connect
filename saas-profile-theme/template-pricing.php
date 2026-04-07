<?php
/**
 * Template Name: Pricing Page
 */

get_header(); ?>

<?php $p_title = get_option('saas_pricing_title') ?: 'Simple, Transparent Pricing'; ?>
<main id="pricing-page" class="site-main site-container" style="max-width: 1100px; margin: 80px auto; text-align: center; padding: 0 20px;">
    <header class="section-header" style="margin-bottom: 60px;">
        <h1 style="font-size: 3rem; margin-bottom: 20px;"><?php echo esc_html($p_title); ?></h1>
        <p style="font-size: 1.25rem;">Choose the plan that's right for your business growth.</p>
    </header>

    <div class="pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
        <div class="price-card" style="background:#fff; padding:40px; border-radius:32px; box-shadow:0 15px 40px rgba(0,0,0,0.05); border:1px solid #eee;">
            <h2>Free</h2>
            <div class="price" style="font-size:3rem; font-weight:800; margin:20px 0;">$0</div>
            <ul style="list-style:none; padding:0; margin:30px 0; text-align:left; color:#666;">
                <li style="margin-bottom:12px;">✓ 1 Profile</li>
                <li style="margin-bottom:12px;">✓ 5 Links</li>
                <li style="margin-bottom:12px;">✓ Basic Analytics</li>
            </ul>
            <a href="<?php echo home_url('/register'); ?>" class="saas-cta-btn-vibrant">Get Started</a>
        </div>

        <div class="price-card featured" style="background:#fff; padding:40px; border-radius:32px; box-shadow:0 25px 60px rgba(108, 92, 231, 0.15); border:2px solid #6c5ce7; transform: scale(1.05);">
            <div class="badge" style="background:#6c5ce7; color:#fff; display:inline-block; padding:5px 15px; border-radius:20px; font-size:0.8rem; margin-bottom:15px;">Most Popular</div>
            <h2>Pro</h2>
            <div class="price" style="font-size:3rem; font-weight:800; margin:20px 0; color:#6c5ce7;">$19</div>
            <ul style="list-style:none; padding:0; margin:30px 0; text-align:left;">
                <li style="margin-bottom:12px;">✓ Unlimited Profiles</li>
                <li style="margin-bottom:12px;">✓ Advanced Analytics</li>
                <li style="margin-bottom:12px;">✓ Custom Domain Support</li>
                <li style="margin-bottom:12px;">✓ No Branding</li>
            </ul>
            <a href="<?php echo home_url('/register'); ?>" class="saas-cta-btn-vibrant">Go Pro Now</a>
        </div>
    </div>
</main>

<?php get_footer(); ?>
