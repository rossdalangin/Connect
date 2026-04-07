<!-- Benefits Section -->
<section style="padding: 120px 20px; background: #fff; position: relative; z-index: 1;">
    <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: center; gap: 80px;">
        <div style="flex: 1; min-width: 320px;">
            <h2 style="font-size: 3.5rem; margin-bottom: 35px; line-height: 1.1; letter-spacing: -1.5px;">Stop losing traffic.<br>Start building your list.</h2>
            <ul style="list-style: none; padding: 0; font-size: 1.3rem; color: #555;">
                <?php
                $benefits = json_decode(get_option('saas_home_benefits'), true) ?: [
                    'One link to rule them all',
                    'Capture leads even while you sleep',
                    'Instant vCard exchange for networking',
                    'Beautiful, mobile-first design'
                ];
                foreach ($benefits as $b) : ?>
                    <li style="margin-bottom: 20px; display: flex; align-items: center; gap: 20px;">
                        <div style="width: 24px; height: 24px; background: #39e09b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #fff;">✓</div>
                        <?php echo esc_html($b); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div style="flex: 1; min-width: 320px;">
            <div style="background: #f8f9fa; padding: 50px; border-radius: 48px; border: 1px solid #eee; position: relative;">
                <div style="position: absolute; top: -30px; right: -30px; background: #6c5ce7; color: #fff; padding: 15px 30px; border-radius: 20px; font-weight: 800; transform: rotate(10deg); box-shadow: 0 10px 20px rgba(108, 92, 231, 0.3);">Live Demo</div>
                <h4 style="margin-top: 0; font-size: 1.5rem; margin-bottom: 10px;">Your Profile Preview</h4>
                <p style="margin-bottom: 30px; color: #888;">See how your business card looks on mobile instantly.</p>
                <div style="width: 100%; height: 380px; background: #fff; border-radius: 32px; border: 10px solid #333; overflow: hidden; position: relative;">
                    <div style="padding: 30px; text-align: center;">
                        <div style="width: 64px; height: 64px; background: #eee; border-radius: 50%; margin: 0 auto 20px;"></div>
                        <div style="width: 140px; height: 12px; background: #eee; margin: 0 auto 15px; border-radius: 6px;"></div>
                        <div style="width: 100px; height: 10px; background: #f3f3f3; margin: 0 auto 30px; border-radius: 5px;"></div>
                        <div style="width: 100%; height: 50px; background: #6c5ce7; border-radius: 50px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 0.8rem;">GET STARTED</div>
                        <div style="width: 100%; height: 50px; background: #f8f9fa; border-radius: 50px; border: 1px solid #eee;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section id="features" style="padding: 140px 20px; background: #f8f9fa; border-top: 1px solid #eee;">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: 3rem; margin-bottom: 20px; letter-spacing: -1px;">Everything you need to grow online</h2>
        <p style="margin-bottom: 80px; font-size: 1.2rem; color: #666; max-width: 600px; margin-left: auto; margin-right: auto;">Powerful tools designed for the modern creator economy.</p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px;">
            <?php
            $features = json_decode(get_option('saas_home_features'), true) ?: [
                ['icon' => '🚀', 'title' => 'Fast Setup', 'desc' => 'Launch your profile in under 60 seconds with our setup wizard.'],
                ['icon' => '📊', 'title' => 'Real-Time Insights', 'desc' => 'Track every click, view, and NFC tap with our performance analytics.'],
                ['icon' => '🎯', 'title' => 'Lead CRM', 'desc' => 'Convert traffic into customers with built-in forms and lead management.'],
                ['icon' => '💾', 'title' => 'Digital Card', 'desc' => 'Instant vCard exchange and QR codes for offline networking.'],
                ['icon' => '🎨', 'title' => 'Custom Branding', 'desc' => 'Your brand, your colors. Full control over every visual element.'],
                ['icon' => '🔒', 'title' => 'Password Protected', 'desc' => 'Secure your premium content with individual link passwords.']
            ];

            foreach ($features as $f) : ?>
                <div style="background:#fff; padding:50px; border-radius:40px; box-shadow:0 15px 50px rgba(0,0,0,0.03); transition: transform 0.4s; border: 1px solid #eee; text-align: left;" onmouseover="this.style.transform='translateY(-15px)'" onmouseout="this.style.transform='none'">
                    <div style="font-size: 3.5rem; margin-bottom: 25px;"><?php echo esc_html($f['icon']); ?></div>
                    <h3 style="font-size:1.6rem; margin-bottom:15px; font-weight: 800;"><?php echo esc_html($f['title']); ?></h3>
                    <p style="line-height: 1.6; color: #555;"><?php echo esc_html($f['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
