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
        if ( is_user_logged_in() ) return '<p>You already have an account.</p>';

        $requested_username = isset($_GET['username']) ? sanitize_user($_GET['username']) : '';

        // Simple registration form
        ob_start();
        ?>
        <form id="saas-registration-form" method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <?php wp_nonce_field( 'saas_register_nonce', 'saas_register_security' ); ?>
            <input type="hidden" name="action" value="saas_register_user">
            <p><input type="text" name="user_login" placeholder="Username" value="<?php echo esc_attr($requested_username); ?>" required></p>
            <p><input type="email" name="user_email" placeholder="Email" required></p>
            <p><input type="password" name="user_pass" placeholder="Password" required></p>
            <p><button type="submit" class="button button-primary">Create Account</button></p>
        </form>
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
            // Handle Referral attribution
            if ( isset($_COOKIE['saas_ref']) ) {
                $referrer = get_user_by('login', $_COOKIE['saas_ref']);
                if ($referrer) {
                    update_user_meta($user_id, '_saas_referred_by', $referrer->ID);
                }
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
