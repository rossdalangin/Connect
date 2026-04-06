<?php
/**
 * Template Name: Auth Page (Login/Register)
 */

get_header(); ?>

<main id="auth-page" class="site-main" style="min-height: 90vh; display: flex; align-items: center; justify-content: center; background: #f3f3f1;">
    <div class="auth-card" style="width: 100%; max-width: 400px; background: #fff; padding: 40px; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); text-align: center;">
        <div class="auth-logo" style="margin-bottom: 30px;">
            <?php if ( has_custom_logo() ) : the_custom_logo(); else: ?>
                <h2 style="font-weight: 800;"><?php bloginfo('name'); ?></h2>
            <?php endif; ?>
        </div>

        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>
    </div>
</main>

<style>
    #auth-page input[type="text"], #auth-page input[type="email"], #auth-page input[type="password"] {
        width: 100%;
        padding: 14px;
        margin-bottom: 16px;
        border: 1px solid #ddd;
        border-radius: 12px;
        box-sizing: border-box;
    }
    #auth-page .button, #auth-page button {
        width: 100%;
        padding: 14px;
        background: #0073aa;
        color: #fff;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
    }
</style>

<?php get_footer(); ?>
