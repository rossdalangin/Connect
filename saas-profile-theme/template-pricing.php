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

    <div class="pricing-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; align-items: stretch;">
        <?php
        $pricing_json = get_option('saas_home_pricing_json');
        $plans = json_decode($pricing_json, true) ?: [
            [
                'name' => 'Free', 'price' => '$0', 'period' => 'forever', 'cta' => 'Get Started', 'link' => '/register', 'style' => 'light',
                'features' => ['1 Profile', 'Standard Blocks', 'Basic Analytics']
            ],
            [
                'name' => 'Pro', 'price' => '$19', 'period' => '/mo', 'cta' => 'Go Pro Now', 'link' => '/register?plan=pro', 'style' => 'featured', 'badge' => 'Most Popular',
                'features' => ['Unlimited Profiles', 'Advanced Analytics', 'Custom Domain Support', 'No Branding']
            ]
        ];

        foreach ($plans as $p) :
            $is_featured = ($p['style'] === 'featured');
        ?>
            <div class="price-card <?php echo $is_featured ? 'featured' : ''; ?>" style="background:#fff; padding:40px; border-radius:32px; box-shadow:<?php echo $is_featured ? '0 25px 60px rgba(108, 92, 231, 0.15)' : '0 15px 40px rgba(0,0,0,0.05)'; ?>; border:<?php echo $is_featured ? '2px solid #6c5ce7' : '1px solid #eee'; ?>; <?php echo $is_featured ? 'transform: scale(1.05);' : ''; ?> display: flex; flex-direction: column;">
                <?php if (isset($p['badge'])) : ?>
                    <div class="badge" style="background:#6c5ce7; color:#fff; display:inline-block; padding:5px 15px; border-radius:20px; font-size:0.8rem; margin-bottom:15px; align-self: center;"><?php echo esc_html($p['badge']); ?></div>
                <?php endif; ?>
                <h2><?php echo esc_html($p['name']); ?></h2>
                <div class="price" style="font-size:3rem; font-weight:800; margin:20px 0; color:<?php echo $is_featured ? '#6c5ce7' : 'inherit'; ?>;">
                    <?php echo esc_html($p['price']); ?><small style="font-size:1rem; opacity:0.6;"><?php echo esc_html($p['period']); ?></small>
                </div>
                <ul style="list-style:none; padding:0; margin:30px 0; text-align:left; color:#666; flex: 1;">
                    <?php foreach ($p['features'] as $f) : ?>
                        <li style="margin-bottom:12px;">✓ <?php echo esc_html($f); ?></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo home_url($p['link']); ?>" class="saas-cta-btn-vibrant" style="width: 100%; box-sizing: border-box;"><?php echo esc_html($p['cta']); ?></a>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php get_footer(); ?>
