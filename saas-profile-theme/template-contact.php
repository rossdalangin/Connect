<?php
/**
 * Template Name: Contact Page
 */

get_header(); ?>

<main id="contact-page" class="site-main site-container" style="max-width: 800px; margin: 80px auto; padding: 0 20px;">
    <div style="background:#fff; padding:60px; border-radius:32px; box-shadow:0 15px 40px rgba(0,0,0,0.05); text-align:center;">
        <h1 style="font-size: 3rem; margin-bottom: 20px;">Get in Touch</h1>
        <p style="font-size: 1.25rem; margin-bottom: 40px;">Have questions? We're here to help you scale.</p>

        <form style="text-align:left;">
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:700;">Name</label>
                <input type="text" style="width:100%; padding:15px; border:1px solid #eee; border-radius:12px;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:700;">Email</label>
                <input type="email" style="width:100%; padding:15px; border:1px solid #eee; border-radius:12px;">
            </div>
            <div style="margin-bottom:30px;">
                <label style="display:block; margin-bottom:8px; font-weight:700;">Message</label>
                <textarea style="width:100%; padding:15px; border:1px solid #eee; border-radius:12px; min-height:150px;"></textarea>
            </div>
            <button type="submit" class="saas-cta-btn-vibrant" style="width:100%;">Send Message</button>
        </form>
    </div>
</main>

<?php get_footer(); ?>
