<?php
/**
 * Authentication Shortcodes & Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Saas_Auth {
    public function __construct() {
        add_shortcode( 'saas_login_form', [ $this, 'login_form' ] );
        add_shortcode( 'saas_register_form', [ $this, 'register_form' ] );
    }

    public function login_form() {
        if ( is_user_logged_in() ) return '<p>You are already logged in. <a href="'.wp_logout_url().'">Logout</a></p>';

        ob_start();
        wp_login_form([
            'redirect' => home_url( '/dashboard' ),
            'form_id'  => 'saas-login-form',
        ]);
        return ob_get_clean();
    }

    public function register_form() {
        if ( is_user_logged_in() ) return '<p>You already have an account. <a href="'.home_url('/dashboard').'">Go to Dashboard</a></p>';

        $requested_username = isset($_GET['username']) ? sanitize_user($_GET['username']) : '';
        $plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : 'free';

        // Simple registration form
        ob_start();
        ?>
        <div class="saas-auth-card" style="max-width:400px; margin:60px auto; background:#fff; padding:40px; border-radius:24px; box-shadow:0 15px 40px rgba(0,0,0,0.05); border:1px solid #eee;">
            <h2 style="text-align:center; margin-bottom:30px;"><?php echo get_option('saas_register_title') ?: 'Create Your Elite Account'; ?></h2>
            <form id="saas-registration-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <?php wp_nonce_field( 'saas_register_nonce', 'saas_register_security' ); ?>
                <input type="hidden" name="action" value="saas_register_user">
                <input type="hidden" name="target_plan" value="<?php echo esc_attr($plan); ?>">
                <div class="field" style="margin-bottom:15px;"><input type="text" name="user_login" placeholder="Pick a Username" value="<?php echo esc_attr($requested_username); ?>" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;"></div>
                <div class="field" style="margin-bottom:15px;"><input type="email" name="user_email" placeholder="Email Address" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;"></div>
                <div class="field" style="margin-bottom:15px;"><input type="password" name="user_pass" placeholder="Create Password" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;"></div>
                <div class="field" style="margin-bottom:20px;"><input type="text" name="coupon_code" placeholder="Coupon Code (Optional)" style="width:100%; padding:12px; border-radius:10px; border:1px solid #ddd;"></div>
                <p><button type="submit" class="btn-primary" style="width:100%;">Create Account & Continue</button></p>
                <p style="text-align:center; margin-top:20px; font-size:0.9rem;">Already have an account? <a href="<?php echo home_url('/login'); ?>" style="color:var(--primary); font-weight:700;">Login</a></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function handle_registration() {
        if ( $_POST['action'] !== 'saas_register_user' ) return;

        if ( ! isset( $_POST['saas_register_security'] ) || ! wp_verify_nonce( $_POST['saas_register_security'], 'saas_register_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $user_login = sanitize_user( $_POST['user_login'] );
        $user_email = sanitize_email( $_POST['user_email'] );
        $user_pass  = $_POST['user_pass'];

        // Cross-check against WordPress users AND existing SaaS profile slugs
        $profile_exists = get_posts([
            'name'        => $user_login,
            'post_type'   => 'saas_profile',
            'post_status' => 'publish',
            'fields'      => 'ids',
            'numberposts' => 1
        ]);

        if ( username_exists($user_login) || email_exists($user_email) || !empty($profile_exists) ) {
            wp_die('This username or email is already associated with an account or profile. Please try another.');
        }

        $user_id = wp_create_user( $user_login, $user_pass, $user_email );

        if ( ! is_wp_error($user_id) ) {
            // Store target plan from registration form
            if ( isset($_POST['target_plan']) ) {
                update_user_meta($user_id, '_saas_registration_target_plan', sanitize_text_field($_POST['target_plan']));
            }

            // Handle Coupon/Referral attribution
            $coupon = isset($_POST['coupon_code']) ? strtoupper(sanitize_text_field($_POST['coupon_code'])) : '';
            $referrer_id = 0;

            if ($coupon) {
                $coupons = get_option('saas_affiliate_coupons') ?: [];
                foreach ($coupons as $c) {
                    if (strtoupper($c['code']) === $coupon) {
                        $referrer_id = intval($c['user_id']);
                        break;
                    }
                }
            }

            if (!$referrer_id && isset($_COOKIE['saas_ref'])) {
                $referrer = get_user_by('login', $_COOKIE['saas_ref']);
                if ($referrer) $referrer_id = $referrer->ID;
            }

            if ($referrer_id) {
                update_user_meta($user_id, '_saas_referred_by', $referrer_id);
            }

            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
            wp_redirect( home_url('/dashboard') );
            exit;
        }
    }
}
add_action( 'admin_post_saas_register_user', [ 'Saas_Auth', 'handle_registration' ] );
add_action( 'admin_post_nopriv_saas_register_user', [ 'Saas_Auth', 'handle_registration' ] );
new Saas_Auth();
