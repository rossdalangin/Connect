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

<!-- Unified Conversion Sections -->
<?php include __DIR__ . '/template-parts/content-hero.php'; ?>

<!-- Comparison Section -->
<?php
$comparison_json = get_option('saas_home_comparison_json');
if ($comparison_json) : ?>
<section style="padding: 120px 20px; background: #fff;">
    <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 3.5rem; font-weight: 900; margin-bottom: 60px;">Why elite creators choose us</h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; background: #fff; border-radius: 32px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.05);">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 30px; font-size: 1.2rem;">Feature</th>
                        <th style="padding: 30px; font-size: 1.2rem; color: #94a3b8;">Basic Link Hubs</th>
                        <th style="padding: 30px; font-size: 1.2rem; color: #6c5ce7; font-weight: 900;">Elite SaaS Funnel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rows = json_decode($comparison_json, true);
                    foreach ($rows as $row) : ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 25px 30px; font-weight: 700;"><?php echo esc_html($row['label']); ?></td>
                            <td style="padding: 25px 30px; color: <?php echo strpos($row['basic'], '✗') !== false ? '#ef4444' : '#94a3b8'; ?>;"><?php echo esc_html($row['basic']); ?></td>
                            <td style="padding: 25px 30px; color: #10b981; font-weight: 700;"><?php echo esc_html($row['elite']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials Section -->
<section style="padding: 120px 20px; background: #f8fafc;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 3.5rem; font-weight: 900; margin-bottom: 80px;"><?php echo get_option('saas_home_testimonials_title') ?: 'What elite creators are saying'; ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            <?php
            $t_json = get_option('saas_home_testimonials');
            $testimonials = json_decode($t_json, true) ?: [];
            foreach ($testimonials as $t) : ?>
                <div style="background: #fff; padding: 50px; border-radius: 40px; text-align: left; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                    <div style="color: #f59e0b; font-size: 1.5rem; margin-bottom: 20px;">★★★★★</div>
                    <p style="font-size: 1.15rem; line-height: 1.7; margin-bottom: 30px; color: #475569;">"<?php echo esc_html($t['text']); ?>"</p>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #94a3b8;"><?php echo substr($t['name'], 0, 1); ?></div>
                        <div>
                            <strong style="display: block; font-size: 1.1rem; color: #1e293b;"><?php echo esc_html($t['name']); ?></strong>
                            <small style="color: #64748b; font-weight: 600;"><?php echo esc_html($t['role']); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section style="padding: 100px 20px; background: #fff;">
    <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 2.5rem; margin-bottom: 60px;"><?php echo get_option('saas_pricing_title') ?: 'Simple, Transparent Pricing'; ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; align-items: stretch;">
            <?php
            $pricing_json = get_option('saas_home_pricing_json');
            $plans = json_decode($pricing_json, true) ?: [];
            foreach ($plans as $p) :
                $is_featured = ($p['style'] === 'featured');
            ?>
                <div style="padding: 40px; border-radius: 32px; <?php echo $is_featured ? 'background: #6c5ce7; color: #fff; position: relative; transform: scale(1.05); box-shadow: 0 20px 50px rgba(108, 92, 231, 0.2);' : 'border: 1px solid #eee; background: #fff;'; ?> display: flex; flex-direction: column;">
                    <?php if (isset($p['badge'])) : ?>
                        <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: #39e09b; padding: 5px 20px; border-radius: 50px; font-size: 0.8rem; font-weight: 800; color: #1e2329;"><?php echo esc_html($p['badge']); ?></div>
                    <?php endif; ?>
                    <h3><?php echo esc_html($p['name']); ?></h3>
                    <div style="font-size: 3rem; font-weight: 800; margin: 20px 0;"><?php echo esc_html($p['price']); ?><small style="font-size:1rem; opacity:0.7;"><?php echo esc_html($p['period']); ?></small></div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 30px; <?php echo $is_featured ? 'color: rgba(255,255,255,0.8);' : 'color: #666;'; ?> flex: 1;">
                        <?php foreach ($p['features'] as $f) : ?>
                            <li style="margin-bottom:10px;">✓ <?php echo esc_html($f); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?php echo home_url($p['link']); ?>" style="display: block; padding: 15px; border-radius: 50px; text-decoration: none; font-weight: 700; <?php echo $is_featured ? 'background: #fff; color: #6c5ce7;' : 'border: 2px solid #6c5ce7; color: #6c5ce7;'; ?>">
                        <?php echo esc_html($p['cta']); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Founder's Letter Section -->
<section style="padding: 100px 20px; background: #f8fafc;">
    <div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 60px; border-radius: 40px; border: 1px solid #e2e8f0; box-shadow: 0 10px 40px rgba(0,0,0,0.02);">
        <div style="display: flex; gap: 30px; align-items: center; margin-bottom: 30px;">
            <?php
            $founder_img = get_option('saas_home_founder_image');
            if ($founder_img) : ?>
                <img src="<?php echo esc_url($founder_img); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
            <?php else : ?>
                <div style="width: 80px; height: 80px; border-radius: 50%; background: #6c5ce7; border: 4px solid #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.1);"></div>
            <?php endif; ?>
            <div>
                <h3 style="margin: 0; font-size: 1.5rem;">A Message from the Founder</h3>
                <p style="margin: 0; color: #64748b;">Consultant & Digital Architect</p>
            </div>
        </div>
        <p style="font-size: 1.25rem; line-height: 1.8; color: #475569; font-style: italic;">
            "<?php echo get_option('saas_home_founder_letter') ?: 'I built this because I saw so many hard-working coaches losing leads to standard link trees. You deserve a system that converts your hard work into results.'; ?>"
        </p>
        <p style="margin-top: 20px; font-weight: 700; color: #6c5ce7;">— Let’s help more people, together.</p>
    </div>
</section>

<!-- FAQ Section -->
<section style="padding: 100px 20px; background: #fff;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 60px;">Common Questions</h2>
        <?php
        $f_json = get_option('saas_home_faq');
        $faqs = json_decode($f_json, true) ?: [];
        foreach ($faqs as $f) : ?>
            <details style="background:#f8fafc; padding:20px; border-radius:16px; margin-bottom:15px; border: 1px solid #e2e8f0;">
                <summary style="font-weight:700; cursor:pointer; outline:none;"><?php echo esc_html($f['q']); ?></summary>
                <p style="margin-top:15px; line-height:1.6; color:#475569;"><?php echo esc_html($f['a']); ?></p>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<style>
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>

<?php get_footer(); ?>
