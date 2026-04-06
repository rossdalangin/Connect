# SaaS Database Schema

The system uses WordPress's native tables (`wp_users`, `wp_posts`, `wp_postmeta`) plus custom tables for high-volume analytics.

## 1. Custom Post Types (CPTs)

### A. `profile` (Native WP Post)
Each user has exactly one `profile` post that stores their bio/metadata.
- **Title:** User Display Name
- **Slug:** `username` (the routing key for `yourdomain.com/username`)
- **Author:** WP User ID
- **Meta Fields:**
  - `_bio`: Text (short biography)
  - `_headline`: Text (short tagline)
  - `_avatar_url`: URL (profile picture)
  - `_cover_url`: URL (banner image)
  - `_social_links`: Array (platform => URL)
  - `_theme_color`: Hex code
  - `_vcard_data`: Array (full contact info for .vcf generation)

### B. `link` (Native WP Post)
Individual links associated with a user profile.
- **Title:** Link Label
- **Author:** WP User ID
- **Meta Fields:**
  - `_link_url`: URL (destination)
  - `_link_icon`: String (FontAwesome or SVG path)
  - `_priority`: Integer (for manual drag-and-drop sorting)
  - `_conditional_logic`: Array (device, geo, schedule rules)
  - `_link_style`: String (e.g., 'featured', 'regular', 'outline')

### C. `lead` (Native WP Post)
Captured leads from profile forms.
- **Title:** Lead Name/Email
- **Author:** WP User ID (the profile owner who received the lead)
- **Meta Fields:**
  - `_lead_name`: Text
  - `_lead_email`: Text
  - `_lead_phone`: Text
  - `_lead_status`: String ('new', 'contacted', 'converted')
  - `_lead_source_url`: URL (which link or page triggered the lead)
  - `_lead_tags`: Array

## 2. Custom Analytics Table: `wp_saas_analytics`
Designed for performance, avoiding postmeta overhead for high-traffic events.

| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | BIGINT | Auto-increment PK |
| `user_id` | BIGINT | Owner of the profile/link |
| `event_type` | VARCHAR(50) | 'view', 'click', 'lead_gen' |
| `target_id` | BIGINT | Post ID (Profile ID or Link ID) |
| `ip_address` | VARCHAR(45) | For deduplication/spam control |
| `user_agent` | TEXT | For device/browser stats |
| `referrer` | TEXT | Traffic source |
| `created_at` | DATETIME | Timestamp of event |

## 3. Custom Settings Table: `wp_saas_settings` (Optional)
Global and per-user configuration for the platform.
- `key`: 'stripe_api_key', 'paypal_email', 'plan_limits', etc.
- `value`: JSON/Serialized data.

## 4. Payment & Subscriptions
- Uses `wp_users` meta to store `_subscription_plan`, `_stripe_customer_id`, `_subscription_expiry`.
