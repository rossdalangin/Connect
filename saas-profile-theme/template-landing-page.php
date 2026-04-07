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

        <!-- Social Proof Logos -->
        <div class="trusted-by" style="margin-bottom: 60px;">
            <p style="text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; color: #a0a0a0; margin-bottom: 20px;">Trusted by innovators at</p>
            <div style="display: flex; justify-content: center; gap: 40px; filter: grayscale(1); opacity: 0.5;">
                <?php
                $logos_json = get_option('saas_home_trusted_logos');
                $logos = json_decode($logos_json, true) ?: [
                    'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg',
                    'https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg',
                    'https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg'
                ];
                foreach ($logos as $logo_url) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" style="height: 24px;">
                <?php endforeach; ?>
            </div>
        </div>

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

<!-- Benefits Section -->
<section style="padding: 100px 20px; background: #fff;">
    <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; gap: 60px;">
        <div style="flex: 1; min-width: 300px;">
            <h2 style="font-size: 3rem; margin-bottom: 30px;">Stop losing traffic. Start building your list.</h2>
            <ul style="list-style: none; padding: 0; font-size: 1.2rem; color: #555;">
                <?php
                $benefits = json_decode(get_option('saas_home_benefits'), true) ?: [
                    'One link to rule them all',
                    'Capture leads even while you sleep',
                    'Instant vCard exchange for networking',
                    'Beautiful, mobile-first design'
                ];
                foreach ($benefits as $b) : ?>
                    <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 15px;">
                        <span style="color: #6c5ce7; font-weight: 900;">✓</span> <?php echo esc_html($b); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div style="flex: 1; min-width: 300px; background: #f8f9fa; padding: 40px; border-radius: 40px; border: 1px solid #eee;">
            <h4 style="margin-top: 0;">Try the Live Demo</h4>
            <p>See how your profile looks on mobile instantly.</p>
            <div style="width: 100%; height: 300px; background: #fff; border-radius: 20px; border: 8px solid #333; overflow: hidden;">
                <div style="padding: 20px; text-align: center;">
                    <div style="width: 50px; height: 50px; background: #eee; border-radius: 50%; margin: 0 auto 10px;"></div>
                    <div style="width: 100px; height: 10px; background: #eee; margin: 0 auto 10px;"></div>
                    <div style="width: 100%; height: 40px; background: #6c5ce7; border-radius: 50px; margin-bottom: 10px;"></div>
                    <div style="width: 100%; height: 40px; background: #eee; border-radius: 50px;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section class="features-section" style="padding: 120px 20px; background: #f8f9fa; border-top: 1px solid #eee;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 2.5rem; margin-bottom: 60px;">Everything you need to grow online</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px;">
            <?php
            $features_json = get_option('saas_home_features');
            $features = json_decode($features_json, true);

            if (!$features) {
                $features = [
                    ['icon' => '🚀', 'title' => 'Fast Setup', 'desc' => 'Launch your profile in under 60 seconds.'],
                    ['icon' => '📊', 'title' => 'Smart Analytics', 'desc' => 'Track every click and view with high-performance tracking.'],
                    ['icon' => '🎯', 'title' => 'Lead Capture', 'desc' => 'Convert traffic into real customers with built-in forms.']
                ];
            }

            foreach ($features as $f) : ?>
                <div style="background:#fff; padding:40px; border-radius:32px; box-shadow:0 10px 40px rgba(0,0,0,0.03); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='none'">
                    <div style="font-size: 3rem; margin-bottom: 20px;"><?php echo esc_html($f['icon']); ?></div>
                    <h3 style="font-size:1.5rem; margin-bottom:15px;"><?php echo esc_html($f['title']); ?></h3>
                    <p><?php echo esc_html($f['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section style="padding: 100px 20px; background: #fff;">
    <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 2.5rem; margin-bottom: 60px;">Trusted by 10,000+ creators</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <?php
            $t_json = get_option('saas_home_testimonials');
            $testimonials = json_decode($t_json, true) ?: [
                ['name' => 'Sarah J.', 'role' => 'Coach', 'text' => 'This tool changed my business. I capture 3x more leads now.'],
                ['name' => 'Mark D.', 'role' => 'Realtor', 'text' => 'The NFC business card feature is a game-changer at events.']
            ];
            foreach ($testimonials as $t) : ?>
                <div style="background: #f8f9fa; padding: 40px; border-radius: 32px; text-align: left;">
                    <p style="font-style: italic; margin-bottom: 20px;">"<?php echo esc_html($t['text']); ?>"</p>
                    <strong><?php echo esc_html($t['name']); ?></strong> - <small><?php echo esc_html($t['role']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section style="padding: 100px 20px; background: #fff;">
    <div style="max-width: 1000px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 2.5rem; margin-bottom: 60px;">Choose your plan</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <div style="padding: 40px; border-radius: 32px; border: 1px solid #eee; background: #fff;">
                <h3>Free</h3>
                <div style="font-size: 3rem; font-weight: 800; margin: 20px 0;">$0</div>
                <ul style="list-style: none; padding: 0; margin-bottom: 30px; color: #666;">
                    <li>Basic Link Hub</li>
                    <li>Standard Analytics</li>
                    <li>Community Support</li>
                </ul>
                <a href="<?php echo home_url('/register'); ?>" style="display: block; padding: 15px; border: 2px solid #6c5ce7; border-radius: 50px; color: #6c5ce7; text-decoration: none; font-weight: 700;">Join for Free</a>
            </div>
            <div style="padding: 40px; border-radius: 32px; background: #6c5ce7; color: #fff; position: relative; transform: scale(1.05); box-shadow: 0 20px 50px rgba(108, 92, 231, 0.2);">
                <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: #39e09b; padding: 5px 20px; border-radius: 50px; font-size: 0.8rem; font-weight: 800; color: #1e2329;">MOST POPULAR</div>
                <h3>Pro</h3>
                <div style="font-size: 3rem; font-weight: 800; margin: 20px 0;">$19<small>/mo</small></div>
                <ul style="list-style: none; padding: 0; margin-bottom: 30px; color: rgba(255,255,255,0.8);">
                    <li>Everything in Free</li>
                    <li><strong>Unlimited Premium Blocks</strong></li>
                    <li><strong>Lead Generation Forms</strong></li>
                    <li>Custom Branding & Fonts</li>
                </ul>
                <a href="<?php echo home_url('/register?plan=pro'); ?>" style="display: block; padding: 15px; background: #fff; border-radius: 50px; color: #6c5ce7; text-decoration: none; font-weight: 700;">Upgrade to Pro</a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section style="padding: 100px 20px; background: #f8f9fa;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 60px;">Common Questions</h2>
        <?php
        $f_json = get_option('saas_home_faq');
        $faqs = json_decode($f_json, true) ?: [
            ['q' => 'Is it free?', 'a' => 'Yes, we have a generous free tier for everyone.'],
            ['q' => 'Can I use my own domain?', 'a' => 'Absolutely! Custom domain support is available on Pro plans.']
        ];
        foreach ($faqs as $f) : ?>
            <details style="background:#fff; padding:20px; border-radius:16px; margin-bottom:15px; box-shadow:0 4px 10px rgba(0,0,0,0.02);">
                <summary style="font-weight:700; cursor:pointer; outline:none;"><?php echo esc_html($f['q']); ?></summary>
                <p style="margin-top:15px;"><?php echo esc_html($f['a']); ?></p>
            </details>
        <?php endforeach; ?>
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
