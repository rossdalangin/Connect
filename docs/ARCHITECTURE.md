# SaaS System Architecture - Link-in-Bio & Lead Gen

## 1. System Overview
The system is built as a WordPress-based SaaS. It uses a **Theme** for the public-facing user profiles and a **Companion Plugin** for the core logic, admin management, and user dashboard.

### Core Components:
- **Public Profile (Theme):** Fast, mobile-first page at `yourdomain.com/username`.
- **User Dashboard (Plugin):** Where users manage their links, profile, leads, and analytics.
- **Admin Panel (WP Admin):** Global settings, user management, and payment configuration.
- **Data Layer:** Uses WordPress Custom Post Types (CPT) and custom tables for analytics.

## 2. Component Relationships
```text
[ User ] <--> [ Frontend Profile (Theme) ] <--> [ Analytics Table ]
   ^                  |
   |                  v
   |          [ Lead Gen Form ] ----> [ Leads CPT ]
   |                  ^
   |                  |
[ User Dashboard ] <---
   |
   +--> [ Profiles CPT ]
   +--> [ Links CPT ]
   +--> [ Payment Gateway (Stripe/PayPal) ]
```

## 3. UI Wireframes (Text-Based)

### A. Public Profile (Mobile View)
```text
+-----------------------+
|    [ Profile Pic ]    |
|      @username        |
|    Headline/Bio       |
+-----------------------+
| [ SAVE CONTACT (vCard)]| <-- Sticky or Top CTA
+-----------------------+
| [ FEATURED LINK 1 ]   |
| [ LINK 2          ]   |
| [ LINK 3          ]   |
+-----------------------+
|    [ LEAD FORM ]      |
| Name: [_______]       |
| Email: [_______]      |
| [ GET FREE GUIDE ]    |
+-----------------------+
| [ SOCIAL ICONS ]      |
+-----------------------+
```

### B. User Dashboard
```text
+-----------------------+
| [ Links ] [ Profile ] |
| [ Leads ] [ Analytics]|
+-----------------------+
| Add New Link:         |
| [ Title ] [ URL ]     |
| [ ( + ) ADD ]         |
+-----------------------+
| Current Links:        |
| :: [ My Website ] [X] |
| :: [ LinkedIn ]   [X] |
+-----------------------+
```

## 4. Logic Flows
- **User Signup:** Creates a WP User + empty Profile CPT.
- **Link Creation:** Links are stored as 'Link' CPT items associated with a 'Profile' or User ID.
- **Lead Capture:** Form submission triggers an AJAX call, saves to 'Lead' CPT, and sends email/webhook.
- **Analytics:** Each visit/click increments a record in the custom analytics table.
