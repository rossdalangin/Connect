<?php
/**
 * Template Name: Landing Page (Clean)
 */

get_header(); ?>

<main id="landing-page" class="site-main" style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 100px 20px; background: #fff;">
    <div class="landing-content" style="max-width: 800px; text-align: center;">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <header class="landing-header" style="margin-bottom: 40px;">
                <h1 style="font-size: 3.5rem; font-weight: 800; line-height: 1.1;"><?php the_title(); ?></h1>
            </header>

            <div class="landing-body" style="font-size: 1.25rem; color: #666;">
                <?php the_content(); ?>
            </div>
            <?php
        endwhile;
        ?>
    </div>
</main>

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
