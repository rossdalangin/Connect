<?php
/**
 * Utility Features: vCard & QR Code
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Handle vCard Download
add_action( 'init', 'saas_handle_vcard_download' );
function saas_handle_vcard_download() {
    if ( isset( $_GET['saas_action'] ) && $_GET['saas_action'] === 'vcard' ) {
        $profile_param = $_GET['profile'] ?? '';
        $profile = null;

        if ( is_numeric($profile_param) ) {
            $profile = get_post( intval($profile_param) );
        } elseif ( !empty($profile_param) ) {
            $profile = saas_get_profile_by_slug( sanitize_title($profile_param) );
        }

        if ( ! $profile || $profile->post_type !== 'saas_profile' ) return;

        $profile_id = $profile->ID;
        $meta = saas_get_profile_meta( $profile_id );

        $vcard = "BEGIN:VCARD\n";
        $vcard .= "VERSION:3.0\n";
        $vcard .= "FN:" . $profile->post_title . "\n";
        $vcard .= "ORG:" . (get_post_meta($profile_id, '_saas_company', true) ?: '') . "\n";
        $vcard .= "TITLE:" . $meta['headline'] . "\n";
        $vcard .= "TEL;TYPE=CELL:" . ($meta['phone'] ?: '') . "\n";
        $vcard .= "EMAIL;TYPE=INTERNET:" . get_the_author_meta('user_email', $profile->post_author) . "\n";
        $vcard .= "URL:" . home_url('/' . $profile->post_name) . "\n";

        // Photo integration (Base64)
        $avatar_id = $meta['avatar_id'];
        if ( $avatar_id ) {
            $path = get_attached_file( $avatar_id );
            if ( $path && file_exists($path) ) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $vcard .= "PHOTO;TYPE=" . strtoupper($type) . ";ENCODING=B:" . base64_encode($data) . "\n";
            }
        }

        // Add social links to vCard
        if ( is_array($meta['social_links']) ) {
            foreach ($meta['social_links'] as $platform => $url) {
                $vcard .= "X-SOCIALPROFILE;TYPE=" . strtoupper($platform) . ":" . $url . "\n";
            }
        }

        $vcard .= "NOTE:" . str_replace("\n", "\\n", $meta['bio']) . "\n";
        $vcard .= "END:VCARD";

        header('Content-Type: text/vcard');
        header('Content-Disposition: attachment; filename="' . sanitize_title($profile->post_title) . '.vcf"');
        echo $vcard;
        exit;
    }
}

/**
 * QR Code Generator Utility
 */
function saas_get_profile_qr_url( $profile_slug, $color = '000000' ) {
    $profile_url = home_url( '/' . $profile_slug );
    $color = str_replace('#', '', $color);
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&color=$color&data=" . urlencode($profile_url);
}

/**
 * Data Helpers (Guarded for Theme Compatibility)
 */
if ( ! function_exists( 'saas_get_profile_meta' ) ) {
    function saas_get_profile_meta( $profile_id ) {
        return [
            'bio'          => get_post_meta( $profile_id, '_saas_bio', true ),
            'headline'     => get_post_meta( $profile_id, '_saas_headline', true ),
            'theme_color'  => get_post_meta( $profile_id, '_saas_theme_color', true ) ?: '#6c5ce7',
            'social_links' => get_post_meta( $profile_id, '_saas_social_links', true ) ?: [],
            'phone'        => get_post_meta( $profile_id, '_saas_phone', true ),
            'avatar_id'    => get_post_thumbnail_id( $profile_id ),
            'cover_id'     => get_post_meta( $profile_id, '_saas_cover_id', true ),
            'niche'        => get_post_meta( $profile_id, '_saas_niche', true ),
            'company'      => get_post_meta( $profile_id, '_saas_company', true ),
            'custom_domain'=> get_post_meta( $profile_id, '_saas_custom_domain', true ),
            'bg_type'      => get_post_meta( $profile_id, '_saas_bg_type', true ) ?: 'flat',
            'bg_color'     => get_post_meta( $profile_id, '_saas_bg_color', true ) ?: '#f3f3f1',
            'bg_gradient'  => get_post_meta( $profile_id, '_saas_bg_gradient', true ),
            'btn_shape'    => get_post_meta( $profile_id, '_saas_btn_shape', true ) ?: 'pill',
            'font_family'  => get_post_meta( $profile_id, '_saas_font_family', true ) ?: "'Inter', sans-serif",
            'shadow_style' => get_post_meta( $profile_id, '_saas_container_shadow', true ) ?: 'soft',
            'profile_theme'=> get_post_meta($profile_id, '_saas_profile_theme', true) ?: 'light',
            'custom_css'   => get_post_meta($profile_id, '_saas_custom_css', true),
            'qr_color'     => get_post_meta($profile_id, '_saas_qr_color', true) ?: '#000000',
        ];
    }
}

/**
 * Conditional Routing Helper
 */
if ( ! function_exists( 'saas_get_effective_url' ) ) {
    function saas_get_effective_url( $block_id, $default_url ) {
        // 1. Device-based routing
        $mobile_url = get_post_meta($block_id, '_saas_url_mobile', true);
        if ($mobile_url) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ( stripos($user_agent, 'mobile') !== false ) {
                return $mobile_url;
            }
        }

        // 2. Geo-based routing
        $geo_url = get_post_meta($block_id, '_saas_url_geo', true);
        $target_country = get_post_meta($block_id, '_saas_url_geo_country', true);
        if ($geo_url && $target_country) {
            $visitor_country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'US';
            if ( strtoupper($visitor_country) === strtoupper($target_country) ) {
                return $geo_url;
            }
        }

        return $default_url;
    }
}

/**
 * Custom query to find profile by slug
 */
if ( ! function_exists( 'saas_get_profile_by_slug' ) ) {
    function saas_get_profile_by_slug( $slug ) {
        $posts = get_posts([
            'name'        => $slug,
            'post_type'   => 'saas_profile',
            'post_status' => 'publish',
            'numberposts' => 1
        ]);
        return $posts ? $posts[0] : null;
    }
}

/**
 * License Validation Helper
 */
function saas_is_profile_licensed( $profile_id ) {
    $author_id = get_post_field( 'post_author', $profile_id );

    // 1. Check if user is a PRO subscriber
    $payments = new Saas_Payments();
    if ( $payments->is_pro_user( $author_id ) ) {
        return true;
    }

    // 2. Check license key
    $license_key = get_post_meta( $profile_id, '_saas_license_key', true );
    if ( ! empty($license_key) ) {
        $licenses = get_posts([
            'post_type'   => 'saas_license',
            'author'      => $author_id,
            'title'       => $license_key,
            'post_status' => 'publish',
            'numberposts' => 1
        ]);

        if ( ! empty($licenses) ) {
            $expiry = get_post_meta( $licenses[0]->ID, '_saas_license_expiry', true );
            if ( ! $expiry || $expiry > time() ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Global Template Registry
 */
function saas_get_default_templates() {
    return [
        'coach' => [
            'headline' => 'Helping you double your revenue in 90 days. 🚀',
            'bio' => 'Certified high-performance coach. I work with CEOs and founders to scale their impact.',
            'color' => '#6c5ce7', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => '👉 Free Strategy Session', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Watch Case Study', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Client Success Stories', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Working with Alex was the best decision for my agency.'],
                ['title' => 'Consulting Packages', 'url' => '#', 'type' => 'pricing', 'extra' => "$2,500/mo\nBi-weekly Calls\nSlack Support\nResource Library"],
            ]
        ],
        'business' => [
            'headline' => 'Innovative Solutions for Global Enterprise. 🏢',
            'bio' => 'Streamlining operations and driving growth through technology.',
            'color' => '#0073aa', 'theme' => 'light', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Book a Consultation', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Our Core Services', 'url' => '#', 'type' => 'pricing', 'extra' => "$199/hr\nStrategy Audit\nProcess Automation\nCustom Dev"],
                ['title' => 'FAQ', 'url' => '#', 'type' => 'faq', 'extra' => 'We operate 24/7 across the globe.'],
                ['title' => 'Office Location', 'url' => 'https://maps.google.com', 'type' => 'button'],
            ]
        ],
        'startup' => [
            'headline' => 'Disrupting the Status Quo with Elite Innovation. 🚀',
            'bio' => 'We build scalable solutions for the modern world. Backed by top-tier VCs.',
            'color' => '#6c5ce7', 'theme' => 'dark', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Join our Beta', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Watch Pitch Deck', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Product Roadmap', 'url' => '#', 'type' => 'milestone', 'extra' => 'Progress:65%'],
            ]
        ],
        'wellness' => [
            'headline' => 'Holistic Wellness for the Modern Professional. 🌿',
            'bio' => 'Mind, body, and spirit alignment. Certified wellness coach and nutritionist.',
            'color' => '#00b894', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Free Meditation Session', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Wellness Retreats', 'url' => '#', 'type' => 'pricing', 'extra' => "$1,500+\n3 Days / 2 Nights\nAll Inclusive\nPersonalized Plan"],
                ['title' => 'Client Success Story', 'url' => '#', 'type' => 'testimonial', 'extra' => 'I feel more balanced and energized than ever!'],
            ]
        ],
        'photography' => [
            'headline' => 'Capturing Moments, Telling Stories. 📸',
            'bio' => 'Award-winning lifestyle and commercial photographer based in NYC.',
            'color' => '#2d3436', 'theme' => 'light', 'shadow' => 'hard',
            'links' => [
                ['title' => 'View Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Wedding\nhttps://via.placeholder.com/400?text=Nature"],
                ['title' => 'Book a Shoot', 'url' => '#', 'type' => 'calendar'],
                ['title' => 'Print Store', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
            ]
        ],
        'agency' => [
            'headline' => 'Scaling Brands through Performance Marketing. 🏢',
            'bio' => 'We build high-performance funnels that drive revenue for elite founders.',
            'color' => '#111827', 'theme' => 'dark', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Get a Free Quote', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Our Pricing Models', 'url' => '#', 'type' => 'pricing', 'extra' => "$2,500+\nFull CRM Sync\nScale Strategy"],
                ['title' => 'Latest Campaign Results', 'url' => '#', 'type' => 'video'],
            ]
        ],
        'realtor' => [
            'headline' => 'Bespoke Advisory for Elite Homeowners. 🏡',
            'bio' => 'Specializing in off-market luxury listings. Member of the Top 0.1% Global Network.',
            'color' => '#2d3436', 'theme' => 'dark', 'shadow' => 'none',
            'links' => [
                ['title' => 'New Off-Market Listings', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/800x600?text=Penthouse+A\nhttps://via.placeholder.com/800x600?text=Coastal+Villa"],
                ['title' => 'Request Private Showing', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Quarterly Market Report', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Sales Target Progress', 'url' => '#', 'type' => 'milestone', 'extra' => 'Volume:$42M'],
            ]
        ],
        'saas' => [
            'headline' => 'Software that Scales with Your Ambition. 💻',
            'bio' => 'Building the next generation of digital tools for elite teams. Fast, secure, and intuitive.',
            'color' => '#4834d4', 'theme' => 'dark', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Start Your Free Trial', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Watch Product Demo', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Enterprise Pricing', 'url' => '#', 'type' => 'pricing', 'extra' => "$499/mo\nSSO Support\nDedicated Account Manager\n99.9% SLA"],
                ['title' => 'Technical Documentation', 'url' => '#', 'type' => 'button'],
            ]
        ],
        'fitness' => [
            'headline' => 'Transform Your Body, Elevate Your Life. 🏋️',
            'bio' => 'Certified Elite Trainer. Helping high-performers build sustainable fitness habits that last.',
            'color' => '#ff4757', 'theme' => 'vibrant', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Apply for 1-on-1 Coaching', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Free 7-Day Meal Plan', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Transformation Gallery', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Result+1\nhttps://via.placeholder.com/400?text=Result+2"],
                ['title' => 'Client Success Log', 'url' => '#', 'type' => 'milestone', 'extra' => 'Lbs Lost:4,200+'],
            ]
        ],
        'medical' => [
            'headline' => 'Modern Care, Compassionate Service. 🩺',
            'bio' => 'Full-service medical clinic specializing in preventative wellness and elite diagnostic care.',
            'color' => '#1e90ff', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Book Appointment', 'url' => '#', 'type' => 'calendar'],
                ['title' => 'Patient Portal Login', 'url' => '#', 'type' => 'button'],
                ['title' => 'New Patient Forms', 'url' => '#', 'type' => 'button', 'style' => 'outline'],
                ['title' => 'Common Health FAQs', 'url' => '#', 'type' => 'faq', 'extra' => 'We are open Mon-Fri, 8am - 6pm.'],
            ]
        ],
        'luxury' => [
            'headline' => 'Bespoke Private Advisory. ⚜️',
            'bio' => 'Curating exclusive opportunities for the discerning individual.',
            'color' => '#d4af37', 'theme' => 'luxury', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Inquire Privately', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Exclusive Asset Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/800x600?text=Asset+1\nhttps://via.placeholder.com/800x600?text=Asset+2"],
                ['title' => 'Secure Documentation', 'url' => '#', 'type' => 'button', 'style' => 'outline'],
                ['title' => 'Save VCard to Phone', 'url' => home_url('/?saas_action=vcard'), 'type' => 'button', 'style' => 'rainbow'],
            ]
        ],
        'tiktok' => [
            'headline' => 'Shop My Top Tech & Setup Finds 🛍️',
            'bio' => 'Sharing the best tech deals and office aesthetic finds. Check my links for exclusive discounts!',
            'color' => '#ff0050', 'theme' => 'vibrant', 'shadow' => 'hard',
            'links' => [
                ['title' => 'My Amazon Storefront', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
                ['title' => 'Flash Sale Ending Soon! ⏳', 'url' => '#', 'type' => 'countdown', 'extra' => date('Y-m-d H:i', strtotime('+12 hours'))],
                ['title' => 'Join My Private Discord', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Latest Setup Tour', 'url' => 'https://tiktok.com', 'type' => 'video'],
            ]
        ],
        'influencer' => [
            'headline' => 'Daily Tech Inspo & Lifestyle Hacks. 📸',
            'bio' => 'Sharing the journey with 1M+ followers. Check my links for exclusive gear deals!',
            'color' => '#f8a5c2', 'theme' => 'vibrant', 'shadow' => 'hard',
            'links' => [
                ['title' => 'My Amazon Finds', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
                ['title' => 'Latest YouTube Video', 'url' => '#', 'type' => 'video'],
                ['title' => 'Brand Collaboration', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Exclusive Discord', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
            ]
        ],
        'artist' => [
            'headline' => 'Visual Storytelling through Digital Art. 🎨',
            'bio' => 'Independent designer creating immersive visual experiences for forward-thinking brands.',
            'color' => '#f472b6', 'theme' => 'vibrant', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Portfolio Gallery', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400\nhttps://via.placeholder.com/401"],
                ['title' => 'Project Inquiry', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Follow my Process', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
            ]
        ],
        'podcast' => [
            'headline' => 'Deep Dives into the Elite Mindset. 🎙️',
            'bio' => 'New episodes every Tuesday. We interview the world\'s top 1% to deconstruct their success.',
            'color' => '#6c5ce7', 'theme' => 'dark', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Listen on Spotify', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Watch on YouTube', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Be a Guest (Application)', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Latest Episode Show Notes', 'url' => '#', 'type' => 'button'],
            ]
        ],
        'consultant' => [
            'headline' => 'Strategic Advisory for Scaling Founders. 🧠',
            'bio' => 'I help businesses streamline operations and maximize efficiency through data-driven strategies.',
            'color' => '#4834d4', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Book Audit Call', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Service Menu', 'url' => '#', 'type' => 'pricing', 'extra' => "Operations Audit\nGrowth Strategy\nTeam Training"],
                ['title' => 'Client Testimonial', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Our productivity tripled in 3 months.'],
            ]
        ],
        'lawyer' => [
            'headline' => 'Strategic Legal Advocacy for Elite Clients. ⚖️',
            'bio' => 'Providing sophisticated representation that honors your unique goals.',
            'color' => '#1e3799', 'theme' => 'light', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Schedule Case Review', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Practice Areas', 'url' => '#', 'type' => 'pricing', 'extra' => "Litigation\nCorporate Law\nIP Protection"],
                ['title' => 'Client Success Records', 'url' => '#', 'type' => 'testimonial', 'extra' => 'Unbeatable results in complex litigation.'],
            ]
        ],
        'author' => [
            'headline' => 'Exploring the Intersection of Tech & Humanity. ✍️',
            'bio' => 'Bestselling Author of "The Elite Mindset". Writing at the frontiers of personal growth.',
            'color' => '#4b6584', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => '📘 Buy My Latest Book', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Weekly Newsletter', 'url' => '#', 'type' => 'newsletter'],
                ['title' => 'Latest Blog Posts', 'url' => '#', 'type' => 'button'],
                ['title' => 'Speaking Inquiries', 'url' => '#', 'type' => 'lead_form'],
            ]
        ],
        'doctor' => [
            'headline' => 'Compassionate Care, Precision Medicine. 🩺',
            'bio' => 'Advancing the future of medicine through patient-centered care.',
            'color' => '#0097e6', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Book Appointment', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Patient Portal', 'url' => '#', 'type' => 'button'],
                ['title' => 'Wellness FAQ', 'url' => '#', 'type' => 'faq', 'extra' => 'Available 24/7 for urgent care.'],
            ]
        ],
        'servant' => [
            'headline' => 'Dedicated to Progress & Community. 🏛️',
            'bio' => 'Serving as your advocate in public office. Transparency and Integrity.',
            'color' => '#eb4d4b', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Join My Newsletter', 'url' => '#', 'type' => 'newsletter'],
                ['title' => 'Community Update Video', 'url' => '#', 'type' => 'video'],
                ['title' => 'Volunteer Today', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'My Vision for 2024', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
            ]
        ],
        'course' => [
            'headline' => 'Master Your Craft with Elite Systems. 🎓',
            'bio' => 'Practical, results-driven courses for high-ticket consultants and coaches.',
            'color' => '#0984e3', 'theme' => 'light', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Enroll in Masterclass', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Course Curriculum', 'url' => '#', 'type' => 'pricing', 'extra' => "12 Modules\nWeekly Group Coaching\nPrivate Community\nLifetime Access"],
                ['title' => 'Free 5-Day Mini-Course', 'url' => '#', 'type' => 'lead_form'],
            ]
        ],
        'shop' => [
            'headline' => 'Curated Gear for the Elite Creator. 🛒',
            'bio' => 'Minimalist essentials designed to elevate your workspace and productivity.',
            'color' => '#2d3436', 'theme' => 'vibrant', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Browse Best Sellers', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
                ['title' => 'Elite Mechanical Keyboard', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Keyboard+A\nhttps://via.placeholder.com/400?text=Keyboard+B"],
                ['title' => 'Limited Edition Drop ⏳', 'url' => '#', 'type' => 'countdown', 'extra' => date('Y-m-d H:i', strtotime('+24 hours'))],
            ]
        ],
        'charity' => [
            'headline' => 'Building a Brighter Future Together. ❤️',
            'bio' => 'Empowering communities through sustainable impact and transparent giving.',
            'color' => '#00b894', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Support Our Mission', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'See Our Impact (2023)', 'url' => '#', 'type' => 'milestone', 'extra' => 'Impact:1.2M+ Lives'],
                ['title' => 'Volunteer Information', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Annual Report (PDF)', 'url' => '#', 'type' => 'button'],
            ]
        ],
        'speaker' => [
            'headline' => 'Inspiring Transformation through Keynotes. 🎙️',
            'bio' => 'Helping organizations navigate change and build resilient cultures. Global Keynote Speaker.',
            'color' => '#d4af37', 'theme' => 'luxury', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Watch Highlight Reel', 'url' => '#', 'type' => 'video'],
                ['title' => 'Inquire for Speaking', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Speaker One-Sheet', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
            ]
        ],
        'trainer' => [
            'headline' => 'Elite Performance Coaching. 🏋️‍♀️',
            'bio' => 'Building resilient bodies and minds. 10+ years experience in professional athletics.',
            'color' => '#f0932b', 'theme' => 'vibrant', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Start Your Transformation', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Training Programs', 'url' => '#', 'type' => 'pricing', 'extra' => "Custom Workout\nMeal Plan\nWeekly Check-ins"],
                ['title' => 'Client Results', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=BeforeAfter1\nhttps://via.placeholder.com/400?text=BeforeAfter2"],
            ]
        ],
        'interior_design' => [
            'headline' => 'Elevating Your Living Space. 🛋️',
            'bio' => 'Bespoke interior design for modern homes. Creating functional beauty.',
            'color' => '#7ed6df', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'View Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=LivingRoom\nhttps://via.placeholder.com/400?text=Kitchen"],
                ['title' => 'Book a Consultation', 'url' => '#', 'type' => 'calendar'],
                ['title' => 'Design Packages', 'url' => '#', 'type' => 'pricing', 'extra' => "Room Refresh\nFull Home Design\nVirtual Consult"],
            ]
        ],
        'yoga' => [
            'headline' => 'Find Your Inner Balance. 🧘',
            'bio' => 'Vinyasa and Yin yoga for all levels. Join me on the mat.',
            'color' => '#be2edd', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Join My Next Class', 'url' => '#', 'type' => 'calendar'],
                ['title' => 'Watch Guided Flow', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Yoga For Beginners Guide', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
            ]
        ],
        'coffee_shop' => [
            'headline' => 'Crafting the Perfect Brew. ☕',
            'bio' => 'Locally roasted beans, artisan pastries, and a warm community vibe.',
            'color' => '#6d4c41', 'theme' => 'light', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Our Menu', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=LatteArt\nhttps://via.placeholder.com/400?text=Pastries"],
                ['title' => 'Order for Pickup', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Join Our Loyalty Club', 'url' => '#', 'type' => 'newsletter'],
            ]
        ],
        'non_profit' => [
            'headline' => 'Powering Change, Together. 🤝',
            'bio' => 'Working towards a sustainable future through community-led initiatives.',
            'color' => '#27ae60', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Support Our Cause', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Our Impact Report', 'url' => '#', 'type' => 'milestone', 'extra' => 'Donated:$500k+'],
                ['title' => 'Volunteer Opportunities', 'url' => '#', 'type' => 'lead_form'],
            ]
        ],
        'travel' => [
            'headline' => 'Exploring the World, One City at a Time. ✈️',
            'bio' => 'Full-time traveler and content creator. Sharing the best hidden gems and travel tips.',
            'color' => '#f9ca24', 'theme' => 'vibrant', 'shadow' => 'soft',
            'links' => [
                ['title' => 'My Travel Guides', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Latest Vlog: Bali', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Where I Stayed (Gallery)', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Resort1\nhttps://via.placeholder.com/400?text=Resort2"],
                ['title' => 'Book Your Trip', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
            ]
        ],
        'chef' => [
            'headline' => 'Private Dining & Culinary Excellence. 👨‍🍳',
            'bio' => 'Bespoke culinary experiences for your home. Seasonal, local, and delicious.',
            'color' => '#eb4d4b', 'theme' => 'light', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Inquire for Private Event', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'My Signature Dishes', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Dish1\nhttps://via.placeholder.com/400?text=Dish2"],
                ['title' => 'Weekly Meal Prep', 'url' => '#', 'type' => 'pricing', 'extra' => "5 Meals/Week\n10 Meals/Week\nCustom Macro Plan"],
            ]
        ],
        'makeup' => [
            'headline' => 'Enhancing Your Natural Beauty. 💄',
            'bio' => 'Professional makeup artist for weddings, events, and editorials.',
            'color' => '#ff9ff3', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Book Makeup Service', 'url' => '#', 'type' => 'calendar'],
                ['title' => 'Portfolio Gallery', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Bridal\nhttps://via.placeholder.com/400?text=Editorial"],
                ['title' => 'My Holy Grail Products', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
            ]
        ],
        'web3' => [
            'headline' => 'Building the Future of the Web. 🌐',
            'bio' => 'NFT Collector, DeFi enthusiast, and Web3 developer. Exploring the decentralized world.',
            'color' => '#4834d4', 'theme' => 'dark', 'shadow' => 'soft',
            'links' => [
                ['title' => 'View My NFT Collection', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Join the DAO Discord', 'url' => '#', 'type' => 'button', 'style' => 'featured'],
                ['title' => 'Web3 Consulting', 'url' => '#', 'type' => 'pricing', 'extra' => "Project Audit\nStrategy Design\nDev Support"],
            ]
        ],
        'gaming' => [
            'headline' => 'Level Up Your Gameplay. 🎮',
            'bio' => 'Pro gamer and streamer. Building an elite community of competitive players.',
            'color' => '#6ab04c', 'theme' => 'dark', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Watch Me Live on Twitch', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'My Gaming Setup', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=PC\nhttps://via.placeholder.com/400?text=Peripherals"],
                ['title' => 'Join the Squad (Discord)', 'url' => '#', 'type' => 'button'],
            ]
        ],
        'personal' => [
            'headline' => 'Sharing My Journey & Ideas. ✨',
            'bio' => 'Thinker, dreamer, and digital nomad. Exploring the intersection of design and technology.',
            'color' => '#3498db', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Read My Blog', 'url' => '#', 'type' => 'button'],
                ['title' => 'Current Projects', 'url' => '#', 'type' => 'milestone', 'extra' => 'Learning:React'],
                ['title' => 'Say Hello!', 'url' => '#', 'type' => 'lead_form'],
            ]
        ],
        'mobile_app' => [
            'headline' => 'The App That Changes Everything. 📱',
            'bio' => 'Download our latest mobile experience. Optimized for speed and productivity.',
            'color' => '#55efc4', 'theme' => 'vibrant', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Download on App Store', 'url' => '#', 'type' => 'button', 'style' => 'glow'],
                ['title' => 'Get it on Play Store', 'url' => '#', 'type' => 'button'],
                ['title' => 'App Feature Tour', 'url' => 'https://youtube.com', 'type' => 'video'],
            ]
        ],
        'webinar' => [
            'headline' => 'Unlock the Secrets to High-Ticket Sales. 🎤',
            'bio' => 'Limited-time free training. Learn the exact framework we use to close $10k+ deals.',
            'color' => '#d63031', 'theme' => 'dark', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Register for Webinar', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Webinar Starts In...', 'url' => '#', 'type' => 'countdown', 'extra' => date('Y-m-d H:i', strtotime('+2 hours'))],
                ['title' => 'What You Will Learn', 'url' => '#', 'type' => 'pricing', 'extra' => "Sales Script\nLead Gen\nClosing Flow"],
            ]
        ],
        'musician' => [
            'headline' => 'Sounds of the New Era. 🎵',
            'bio' => 'Independent artist and producer. New album "Elite Vibes" out now on all platforms.',
            'color' => '#fdcb6e', 'theme' => 'vibrant', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Listen on Spotify', 'url' => '#', 'type' => 'button', 'style' => 'rainbow'],
                ['title' => 'New Music Video', 'url' => 'https://youtube.com', 'type' => 'video'],
                ['title' => 'Tour Dates & Tickets', 'url' => '#', 'type' => 'calendar'],
            ]
        ],
        'model' => [
            'headline' => 'High-Fashion & Commercial Talent. 👗',
            'bio' => 'Represented by Elite Agency. Based in Milan/Paris/NYC. Let\'s create magic.',
            'color' => '#e84393', 'theme' => 'luxury', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Modeling Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Runway\nhttps://via.placeholder.com/400?text=Editorial"],
                ['title' => 'Bookings & Inquiry', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'My Comp Card (PDF)', 'url' => '#', 'type' => 'button', 'style' => 'outline'],
            ]
        ],
        'dentist' => [
            'headline' => 'Bespoke Smiles, Modern Care. 🦷',
            'bio' => 'Advanced cosmetic and restorative dentistry. Experience the difference of elite care.',
            'color' => '#81ecec', 'theme' => 'light', 'shadow' => 'soft',
            'links' => [
                ['title' => 'Schedule Appointment', 'url' => '#', 'type' => 'calendar'],
                ['title' => 'Our Smile Gallery', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=Smile1\nhttps://via.placeholder.com/400?text=Smile2"],
                ['title' => 'Dental FAQs', 'url' => '#', 'type' => 'faq', 'extra' => 'We accept all major insurances.'],
            ]
        ],
        'gym' => [
            'headline' => 'Where Elite Performance Begins. 🏢',
            'bio' => '24/7 access, state-of-the-art equipment, and professional personal trainers.',
            'color' => '#2d3436', 'theme' => 'dark', 'shadow' => 'hard',
            'links' => [
                ['title' => 'Claim Free 7-Day Pass', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'Membership Plans', 'url' => '#', 'type' => 'pricing', 'extra' => "$49/mo\nAll Classes\nSauna Access"],
                ['title' => 'Virtual Gym Tour', 'url' => 'https://youtube.com', 'type' => 'video'],
            ]
        ],
        'architecture' => [
            'headline' => 'Designing the Future Landscapes. 📐',
            'bio' => 'Award-winning architectural firm specializing in sustainable luxury residential projects.',
            'color' => '#636e72', 'theme' => 'luxury', 'shadow' => 'soft',
            'links' => [
                ['title' => 'View Projects Portfolio', 'url' => '#', 'type' => 'image_gallery', 'extra' => "https://via.placeholder.com/400?text=ModernVilla\nhttps://via.placeholder.com/400?text=EcoOffice"],
                ['title' => 'Inquire for New Build', 'url' => '#', 'type' => 'lead_form'],
                ['title' => 'The Design Process', 'url' => '#', 'type' => 'milestone', 'extra' => 'Drafting:80%'],
            ]
        ],
    ];
}
