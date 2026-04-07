<?php
/**
 * Template Name: About Page
 */

get_header(); ?>

<main id="about-page" class="site-main site-container" style="max-width: 900px; margin: 100px auto; padding: 0 20px;">
    <?php $vision = get_option('saas_about_vision') ?: 'Empowering 100,000+ creators to own their digital identity.'; ?>
    <div style="text-align: center; margin-bottom: 80px;">
        <h1 style="font-size: 4rem; font-weight: 900; background: linear-gradient(135deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Our Vision</h1>
        <p style="font-size: 1.5rem; color: #636e72;"><?php echo esc_html($vision); ?></p>
    </div>

    <div class="about-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
        <div>
            <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Why we built this.</h2>
            <p style="font-size: 1.15rem; line-height: 1.8;">Social media is great for traffic, but terrible for ownership. We built this platform to give every professional a centralized, high-converting home on the internet that they truly control.</p>
        </div>
        <div style="background: var(--glass-bg); padding: 40px; border-radius: 32px; box-shadow: var(--shadow-soft);">
            <div style="font-size: 3rem; margin-bottom: 10px;">✨</div>
            <h3 style="margin-bottom: 10px;">Conversion First</h3>
            <p>We don't just build link lists. We build lead machines designed by marketing experts.</p>
        </div>
    </div>
</main>

<?php get_footer(); ?>
