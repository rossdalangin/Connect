<?php
// Only show the visual site footer if we're NOT on a user profile page.
if ( ! get_query_var( 'saas_profile' ) ) : ?>
    <footer id="colophon" class="site-footer" style="padding: 100px 0 50px; background: #fff; border-top: 1px solid #eee;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; padding: 0 20px;">
            <div style="flex: 1; min-width: 300px; margin-bottom: 40px;">
                <h3 style="font-size: 1.5rem; font-weight: 800; color: #333; margin-bottom: 20px;"><?php echo get_bloginfo( 'name' ); ?></h3>
                <p style="color: #666; font-size: 0.95rem; max-width: 300px; line-height: 1.6;">Design your digital footprint in under 60 seconds. High-converting link hubs and lead machines.</p>
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <a href="#" style="color: #6c5ce7; font-size: 1.2rem;">𝕏</a>
                    <a href="#" style="color: #6c5ce7; font-size: 1.2rem;">📸</a>
                    <a href="#" style="color: #6c5ce7; font-size: 1.2rem;">💼</a>
                </div>
            </div>
            <div style="flex: 2; display: flex; flex-wrap: wrap; gap: 60px;">
                <div>
                    <h4 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; color: #999; margin-bottom: 25px;">Product</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.95rem;">
                        <li style="margin-bottom: 12px;"><a href="<?php echo home_url('/pricing'); ?>" style="text-decoration: none; color: #666;">Pricing</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Features</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Enterprise</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Demo</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; color: #999; margin-bottom: 25px;">Company</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.95rem;">
                        <li style="margin-bottom: 12px;"><a href="<?php echo home_url('/about'); ?>" style="text-decoration: none; color: #666;">About Us</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Careers</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Blog</a></li>
                        <li style="margin-bottom: 12px;"><a href="<?php echo home_url('/contact'); ?>" style="text-decoration: none; color: #666;">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 2px; color: #999; margin-bottom: 25px;">Legal</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.95rem;">
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Privacy Policy</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Terms of Service</a></li>
                        <li style="margin-bottom: 12px;"><a href="#" style="text-decoration: none; color: #666;">Security</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div style="max-width: 1200px; margin: 60px auto 0; padding: 30px 20px 0; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; font-size: 0.85rem; color: #999;">
            <p>© <?php echo date('Y'); ?> <?php echo get_bloginfo( 'name' ); ?>. All rights reserved.</p>
            <p>Built with ❤️ for Creators.</p>
        </div>
    </footer>
<?php endif; ?>

<?php
$slug = get_query_var( 'saas_profile' );
if ($slug) {
    $profile = saas_get_profile_by_slug($slug);
    if ($profile) {
        $payments = new Saas_Payments();
        if ($payments->is_pro_user($profile->post_author)) {
            echo get_post_meta($profile->ID, '_saas_footer_scripts', true);
        }
    }
}
?>
<?php wp_footer(); ?>
</body>
</html>
