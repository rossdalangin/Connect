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
    <style>
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .sticky-buy { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 1000; display: none; background: #fff; padding: 15px 30px; border-radius: 100px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); border: 1px solid #eee; align-items: center; gap: 20px; animation: slideUp 0.5s ease forwards; }
        @keyframes slideUp { from { bottom: -100px; } to { bottom: 30px; } }
    </style>

    <div class="sticky-buy" id="landing-sticky-cta">
        <p style="margin:0; font-weight:700; color:#1e293b;">Join 12,000+ elite creators today.</p>
        <a href="<?php echo home_url('/register'); ?>" style="background:#6c5ce7; color:#fff; padding:10px 25px; border-radius:50px; text-decoration:none; font-weight:800; font-size:0.9rem;">Get Started Free →</a>
    </div>

    <script>
        window.addEventListener('scroll', function() {
            var cta = document.getElementById('landing-sticky-cta');
            if (window.scrollY > 800) cta.style.display = 'flex';
            else cta.style.display = 'none';
        });
    </script>

    <div class="landing-content" style="max-width: 1000px; text-align: center;">
        <header class="landing-header" style="margin-bottom: 60px; width:100%;">
            <h1 style="font-size: clamp(3rem, 8vw, 5.5rem); font-weight: 900; line-height: 0.9; margin-bottom:30px; background: linear-gradient(135deg, #1e293b, #64748b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: -3px;">
                <?php echo esc_html($h_title); ?>
            </h1>
            <?php if ($h_hero) : ?>
                <p style="font-size: 1.5rem; color: #64748b; font-weight: 500; max-width: 800px; margin: 0 auto; line-height: 1.6;"><?php echo esc_html($h_hero); ?></p>
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

            <div class="hero-claim-wrapper" style="margin-top: 40px;">
                <form action="<?php echo home_url('/register'); ?>" method="GET" style="display:inline-flex; background:#fff; padding:10px; border-radius:100px; box-shadow:0 15px 35px rgba(0,0,0,0.1); border:1px solid #eee; width:100%; max-width:500px;">
                    <span style="padding:0 20px; color:#999; display:flex; align-items:center; font-weight:700;"><?php echo parse_url(home_url(), PHP_URL_HOST); ?>/</span>
                    <input type="text" name="username" id="saas-home-username" placeholder="yourname" style="flex:1; border:none; outline:none; font-size:1.1rem; font-weight:700; padding:10px 0;">
                    <button type="submit" style="background:#6c5ce7; color:#fff; border:none; padding:15px 30px; border-radius:50px; font-weight:800; cursor:pointer; margin-left:10px;"><?php echo esc_html($h_cta); ?></button>
                </form>
                <div id="username-status" style="margin-top:10px; font-size:0.9rem; font-weight:700; height:20px;"></div>
                <p style="font-size:0.8rem; margin-top:15px; color:#a0a0a0;">⚡️ It takes less than 60 seconds to launch.</p>
            </div>
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
        <div style="flex: 1; min-width: 340px; background: #0f172a; padding: 50px; border-radius: 60px; border: 12px solid #1e293b; box-shadow: 0 50px 100px -20px rgba(0,0,0,0.5); position: relative; overflow: hidden;">
            <?php
            $latest_profile = get_posts(['post_type' => 'saas_profile', 'post_status' => 'publish', 'numberposts' => 1]);
            if ($latest_profile) : ?>
                <iframe src="<?php echo home_url('/' . $latest_profile[0]->post_name); ?>" style="width: 100%; height: 500px; border: none; border-radius: 30px; background: #fff;"></iframe>
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer;" onclick="window.location.href='<?php echo home_url('/directory'); ?>'"></div>
            <?php else : ?>
                <div style="text-align: center; color: #fff; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); border-radius: 50%; margin: 0 auto 15px; border: 4px solid #fff;"></div>
                    <h4 style="margin: 0; font-size: 1.5rem; font-weight: 800;">@yourname</h4>
                    <p style="font-size: 0.9rem; opacity: 0.6;">Digital Architect & Creator</p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="height: 50px; background: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0f172a;">🚀 Work with Me</div>
                    <div style="height: 50px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff;">🎬 Latest Masterclass</div>
                    <div style="height: 50px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff;">📦 My Products</div>
                    <div style="margin-top: 20px; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 20px; border: 1px dashed rgba(255,255,255,0.2); text-align: center;">
                        <p style="color: #39e09b; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 10px;">New Lead Captured!</p>
                        <div style="height: 8px; background: #39e09b; border-radius: 10px; width: 80%; margin: 0 auto;"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Comparison Section -->
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
                    $comparison_json = get_option('saas_home_comparison_json');
                    $rows = json_decode($comparison_json, true) ?: [
                        ['label' => 'Lead Generation Forms', 'basic' => '✗ No', 'elite' => '✓ Integrated'],
                        ['label' => 'A/B Split Testing', 'basic' => '✗ No', 'elite' => '✓ Automated'],
                        ['label' => 'NFC Digital Business Card', 'basic' => '✗ No', 'elite' => '✓ Native Sync'],
                        ['label' => 'CRM & Email Integrations', 'basic' => 'Limited', 'elite' => '✓ Full Suite']
                    ];
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

<!-- Growth Stats Section -->
<section style="padding: 100px 20px; background: #0f172a; color: #fff;">
    <?php
    $count_profiles = wp_count_posts('saas_profile')->publish;
    $count_leads = wp_count_posts('saas_lead')->publish;
    global $wpdb;
    $total_rev = (float)$wpdb->get_var("SELECT SUM(meta_value) FROM $wpdb->postmeta WHERE meta_key = '_saas_order_amount'");

    $p_offset = (int) get_option('saas_home_profile_offset') ?: 1250;
    $l_offset = (int) get_option('saas_home_lead_offset') ?: 8500;
    $r_offset = (float) get_option('saas_home_rev_offset') ?: 42.5;
    ?>
    <div style="max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 50px; text-align: center;">
        <div>
            <div style="font-size: 4rem; font-weight: 900; background: linear-gradient(135deg, #6c5ce7, #a29bfe); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo number_format($count_profiles + $p_offset); ?>+</div>
            <p style="font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem;">Elite Profiles Launched</p>
        </div>
        <div>
            <div style="font-size: 4rem; font-weight: 900; background: linear-gradient(135deg, #10b981, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">$<?php echo number_format(($total_rev / 1000000) + $r_offset, 1); ?>M+</div>
            <p style="font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem;">Revenue Processed</p>
        </div>
        <div>
            <div style="font-size: 4rem; font-weight: 900; background: linear-gradient(135deg, #f59e0b, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo number_format($count_leads + $l_offset); ?>+</div>
            <p style="font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem;">High-Intent Leads Captured</p>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section style="padding: 120px 20px; background: #fff;">
    <div style="max-width: 1100px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 3.5rem; font-weight: 900; margin-bottom: 60px;">Your elite presence in 3 simple steps</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px;">
            <?php
            $how_it_works_json = get_option('saas_home_how_it_works_json');
            $steps = json_decode($how_it_works_json, true) ?: [
                ['title' => 'Claim Your Link', 'desc' => 'Register your unique URL and customize your digital identity.'],
                ['title' => 'Build Your Funnel', 'desc' => 'Drag and drop links, forms, and galleries to showcase your best work.'],
                ['title' => 'Launch & Grow', 'desc' => 'Share your link everywhere and watch your conversion rates skyrocket.']
            ];
            $step_num = 1;
            foreach ($steps as $s) : ?>
                <div style="padding: 40px; border-radius: 32px; background: #f8f9fa; border: 1px solid #eee;">
                    <div style="width: 50px; height: 50px; background: #6c5ce7; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; margin: 0 auto 20px;"><?php echo $step_num++; ?></div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 15px;"><?php echo esc_html($s['title']); ?></h3>
                    <p><?php echo esc_html($s['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section class="features-section" style="padding: 120px 20px; background: #f8f9fa; border-top: 1px solid #eee;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 3.5rem; font-weight: 900; margin-bottom: 80px;">Everything you need to grow online</h2>

        <!-- Interactive Tech Preview -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 80px; text-align: left; align-items: center;">
            <div style="background:#fff; padding:50px; border-radius:40px; box-shadow:0 30px 60px rgba(0,0,0,0.05);">
                <div style="display:flex; gap:10px; margin-bottom:20px;">
                    <span style="background:rgba(108, 92, 231, 0.1); color:#6c5ce7; padding:5px 15px; border-radius:50px; font-weight:700; font-size:0.8rem;">Smart Routing</span>
                    <span style="background:rgba(57, 224, 155, 0.1); color:#39e09b; padding:5px 15px; border-radius:50px; font-weight:700; font-size:0.8rem;">A/B Testing</span>
                </div>
                <h3 style="font-size:2rem; margin-bottom:20px;">The only link hub with an IQ.</h3>
                <p style="color:#64748b; font-size:1.1rem; line-height:1.7;">Our system automatically detects your visitor's location and device. Send iPhone users to the App Store and Android users to Play Store—automatically. Run split tests on your CTAs to see which version converts better.</p>
            </div>
            <div style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding:40px; border-radius:40px; color:#fff; position:relative; overflow:hidden;">
                <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); padding:20px; border-radius:15px; margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <strong>Variant A: "Buy Now"</strong>
                        <span style="color:#ef4444;">12.5%</span>
                    </div>
                    <div style="height:6px; background:rgba(255,255,255,0.1); border-radius:10px;"><div style="width:12.5%; height:100%; background:#ef4444; border-radius:10px;"></div></div>
                </div>
                <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); padding:20px; border-radius:15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <strong>Variant B: "Get Started"</strong>
                        <span style="color:#39e09b;">24.8% (Winner)</span>
                    </div>
                    <div style="height:6px; background:rgba(255,255,255,0.1); border-radius:10px;"><div style="width:24.8%; height:100%; background:#39e09b; border-radius:10px;"></div></div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px;">
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

<!-- Theme Showcase Section -->
<section style="padding: 120px 20px; background: #fff;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 3.5rem; font-weight: 900; margin-bottom: 60px;">Bespoke themes for elite brands</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div style="padding: 30px; border-radius: 24px; background: #f8fafc; border: 1px solid #e2e8f0;">
                <div style="height: 200px; background: #fff; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #64748b;">Light Mode</div>
                <h4 style="margin: 0;">Clean & Professional</h4>
            </div>
            <div style="padding: 30px; border-radius: 24px; background: #0f172a; border: 1px solid #1e293b; color: #fff;">
                <div style="height: 200px; background: #1e293b; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #94a3b8;">Dark Mode</div>
                <h4 style="margin: 0;">Modern & Bold</h4>
            </div>
            <div style="padding: 30px; border-radius: 24px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                <div style="height: 200px; background: rgba(255,255,255,0.1); border-radius: 12px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff;">Vibrant</div>
                <h4 style="margin: 0;">Energetic & Fun</h4>
            </div>
            <div style="padding: 30px; border-radius: 24px; background: #000; border: 2px solid #d4af37; color: #d4af37;">
                <div style="height: 200px; background: #1a1a1a; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(212,175,55,0.1); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #d4af37;">Luxury</div>
                <h4 style="margin: 0;">Premium & Elite</h4>
            </div>
        </div>
    </div>
</section>

<!-- Featured Profiles Section -->
<section style="padding: 120px 20px; background: #fff;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Join thousands of elite professionals</h2>
        <p style="color: #636e72; font-size: 1.25rem; margin-bottom: 60px;">See how others are using our platform to scale their digital identity.</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <?php
            $featured_slugs = get_option('saas_featured_profiles') ?: [];
            $featured_profiles = [];

            if ($featured_slugs) {
                $featured_profiles = get_posts([
                    'post_type' => 'saas_profile',
                    'post_name__in' => $featured_slugs,
                    'post_status' => 'publish',
                    'orderby' => 'post_name__in'
                ]);
            }

            if (empty($featured_profiles)) {
                $featured_profiles = get_posts([
                    'post_type' => 'saas_profile',
                    'post_status' => 'publish',
                    'meta_key' => '_saas_show_in_directory',
                    'meta_value' => '1',
                    'numberposts' => 3,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
            }

            if ($featured_profiles) :
                foreach ($featured_profiles as $fp) :
                    $fp_meta = saas_get_profile_meta($fp->ID);
                    $fp_url = home_url('/' . $fp->post_name);
                    ?>
                    <div style="background:#fff; border: 1px solid #eee; padding:40px; border-radius:32px; box-shadow:0 20px 40px rgba(0,0,0,0.03); text-align:center; transition: all 0.3s;" onmouseover="this.style.borderColor='#6c5ce7'; this.style.transform='translateY(-5px)'" onmouseout="this.style.borderColor='#eee'; this.style.transform='none'">
                        <div style="width:100px; height:100px; margin:0 auto 20px; border-radius:50%; overflow:hidden; border:4px solid #f8f9fa;">
                            <?php if (has_post_thumbnail($fp->ID)) : ?>
                                <?php echo get_the_post_thumbnail($fp->ID, 'thumbnail', ['style' => 'width:100%; height:100%; object-fit:cover;']); ?>
                            <?php else : ?>
                                <div style="width:100%; height:100%; background:#eee; display:flex; align-items:center; justify-content:center; font-size:2rem;">👤</div>
                            <?php endif; ?>
                        </div>
                        <h3 style="margin-bottom:5px;"><?php echo esc_html($fp->post_title); ?></h3>
                        <p style="color:#6c5ce7; font-weight:700; font-size:0.9rem; margin-bottom:15px;"><?php echo esc_html($fp_meta['headline']); ?></p>
                        <a href="<?php echo esc_url($fp_url); ?>" target="_blank" style="display:inline-block; padding:10px 24px; background:#f1f2f6; color:#2d3436; text-decoration:none; border-radius:50px; font-weight:700; font-size:0.85rem;">View Profile</a>
                    </div>
                <?php endforeach;
            else: ?>
                <div style="grid-column: 1/-1; padding:40px; background:#f8f9fa; border-radius:24px; color:#999;">
                    Create the first profile to be featured here!
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section style="padding: 120px 20px; background: #fff;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 3.5rem; font-weight: 900; margin-bottom: 80px;"><?php echo get_option('saas_home_testimonials_title') ?: 'What elite creators are saying'; ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
            <?php
            $t_json = get_option('saas_home_testimonials');
            $testimonials = json_decode($t_json, true);

            if (!$testimonials) {
                $testimonials = [
                    ['name' => 'Alex Rivera', 'role' => 'Strategic Coach', 'text' => 'I switched from Linktree and my consultation bookings increased by 40% in the first month.'],
                    ['name' => 'Jordan Smith', 'role' => 'Real Estate Mogul', 'text' => 'The NFC business card feature is the ultimate conversation starter at networking events.'],
                    ['name' => 'Elena Chen', 'role' => 'Digital Artist', 'text' => 'Finally, a link hub that actually looks high-end. The analytics helped me double my revenue.']
                ];
            }

            foreach ($testimonials as $t) : ?>
                <div style="background: #f8fafc; padding: 50px; border-radius: 40px; text-align: left; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
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
        <h2 style="font-size: 2.5rem; margin-bottom: 60px;">Choose your plan</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <?php
            $pricing_json = get_option('saas_home_pricing_json');
            $plans = json_decode($pricing_json, true) ?: [
                [
                    'name' => 'Free', 'price' => '$0', 'period' => 'forever', 'cta' => 'Join for Free', 'link' => '/register', 'style' => 'light',
                    'features' => ['Basic Link Hub', 'Standard Analytics', 'Community Support']
                ],
                [
                    'name' => 'Pro', 'price' => '$19', 'period' => '/mo', 'cta' => 'Upgrade to Pro', 'link' => '/register?plan=pro', 'style' => 'featured', 'badge' => 'MOST POPULAR',
                    'features' => ['Everything in Free', 'Unlimited Premium Blocks', 'Lead Generation Forms', 'Custom Branding & Fonts']
                ]
            ];
            foreach ($plans as $p) :
                $is_featured = ($p['style'] === 'featured');
            ?>
                <div style="padding: 40px; border-radius: 32px; <?php echo $is_featured ? 'background: #6c5ce7; color: #fff; position: relative; transform: scale(1.05); box-shadow: 0 20px 50px rgba(108, 92, 231, 0.2);' : 'border: 1px solid #eee; background: #fff;'; ?>">
                    <?php if (isset($p['badge'])) : ?>
                        <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: #39e09b; padding: 5px 20px; border-radius: 50px; font-size: 0.8rem; font-weight: 800; color: #1e2329;"><?php echo esc_html($p['badge']); ?></div>
                    <?php endif; ?>
                    <h3><?php echo esc_html($p['name']); ?></h3>
                    <div style="font-size: 3rem; font-weight: 800; margin: 20px 0;"><?php echo esc_html($p['price']); ?><small style="font-size:1rem; opacity:0.7;"><?php echo esc_html($p['period']); ?></small></div>
                    <ul style="list-style: none; padding: 0; margin-bottom: 30px; <?php echo $is_featured ? 'color: rgba(255,255,255,0.8);' : 'color: #666;'; ?>">
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
<section style="padding: 100px 20px; background: #fff;">
    <div style="max-width: 800px; margin: 0 auto; background: #f8fafc; padding: 60px; border-radius: 40px; border: 1px solid #e2e8f0;">
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

<!-- Final CTA Section -->
<section style="padding: 120px 20px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: #fff; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h2 style="font-size: 4rem; font-weight: 900; margin-bottom: 20px;">Ready to scale your digital presence?</h2>
        <p style="font-size: 1.5rem; opacity: 0.9; margin-bottom: 40px;">Join thousands of elite creators who are building their future on our platform.</p>
        <a href="<?php echo home_url('/register'); ?>" style="display: inline-block; padding: 25px 60px; background: #39e09b; color: #1e2329; text-decoration: none; border-radius: 100px; font-weight: 900; font-size: 1.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">Get Started for Free</a>
        <p style="margin-top: 20px; font-size: 0.9rem; opacity: 0.7;">No credit card required. Cancel anytime.</p>
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
    .phone-frame {
        width: 320px;
        height: 640px;
        background: #000;
        border-radius: 40px;
        padding: 10px;
        box-shadow: 0 50px 100px -20px rgba(0,0,0,0.3);
        border: 8px solid #1e293b;
        position: relative;
        margin: 0 auto;
    }
    .phone-frame:before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 25px;
        background: #1e293b;
        border-bottom-left-radius: 15px;
        border-bottom-right-radius: 15px;
        z-index: 10;
    }
    .phone-frame iframe {
        width: 100%;
        height: 100%;
        border-radius: 30px;
        background: #fff;
    }

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

<script>
jQuery(document).ready(function($) {
    var timer;
    $('#saas-home-username').on('keyup', function() {
        var user = $(this).val();
        var $status = $('#username-status');
        clearTimeout(timer);

        if (user.length < 3) {
            $status.text('').css('color', 'inherit');
            return;
        }

        $status.text('Checking availability...').css('color', '#666');

        timer = setTimeout(function() {
            $.post(saas_data.ajax_url, {
                action: 'saas_check_username',
                username: user
            }, function(res) {
                if (res.success) {
                    $status.text('✓ ' + user + ' is available!').css('color', '#10b981');
                } else {
                    $status.text('✗ ' + user + ' is already taken.').css('color', '#ef4444');
                }
            });
        }, 500);
    });
});
</script>

<?php get_footer(); ?>
