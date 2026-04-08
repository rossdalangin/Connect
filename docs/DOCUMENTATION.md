# SaaS Documentation & Setup Guide

## 1. Installation (Developer)
1. **Plugin Setup:** Upload the `plugin/` folder contents as a new WordPress plugin. Activate it.
2. **Database:** Upon activation, the `wp_saas_analytics` table will be created automatically.
3. **Theme Setup:** Upload the `theme/` folder as a new theme and activate it.
4. **Permalinks:** Set Permalinks to "Post name" in WP Settings.

## 2. Admin Manual
- **User Management:** Standard WP Users interface.
- **Payment Setup:** Navigate to SaaS Settings (to be added to WP Admin menu) to enter Stripe/PayPal API keys.
- **Global Branding:** Upload a global logo for the login and dashboard pages.

## 3. User Onboarding Guide
1. **Signup:** Register on the landing page.
2. **Claim Profile:** Choose your unique `/username`.
3. **Build Profile:** Upload your photo, write a headline and bio.
4. **Add Links:** Put in your portfolio, social links, or latest project.
5. **Activate Lead Gen:** Turn on the "Work With Me" form.
6. **Share:** Copy your profile link and paste it into your Instagram/TikTok bio.
7. **Track:** Check the Analytics tab to see who is visiting and clicking.
8. **Secure Content:** Protect exclusive links with passwords to gate high-value content.
9. **Export Data:** Download your leads and analytics as CSV for external processing.

## 4. Security & Performance
- **Link Protection:** Password-protected links use server-side AJAX verification to prevent exposure of passwords in frontend code.
- **Analytics:** High-performance custom table structure ensures fast tracking even at scale (1M+ events).
- **Isolation:** Multi-tenant architecture ensures data privacy between users.

## 5. Scaling Plan (Future Roadmap)
- **Whitelabel Domains:** Allow Pro users to map their own custom domains (e.g., `links.sarah.com`).
- **NFC Support:** Full integration with NFC hardware providers.
- **Advanced CRM:** Integration with HubSpot, Mailchimp, and Zapier.
- **Team Plans:** Allow companies to manage 100+ employee profile pages.
- **Multi-language:** Dynamic profile translation based on geo-location.
