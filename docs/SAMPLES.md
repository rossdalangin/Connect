# Sample Data Entries (Blueprints)

## 1. Example User: Sarah Freelance (Coach)
- **WP User ID:** 5
- **Profile Slug:** `sarah-freelance`
- **Headline:** "Transforming Lives Through Digital Strategy."
- **Bio:** "Helping 100+ creative entrepreneurs scale their businesses. Founder of Strategy Academy. Coffee lover."
- **Theme Color:** `#ff5722` (Orange)

## 2. Example Links (Sarah's Profile)
- **Link 1 (Priority):**
  - **Title:** "👉 Book a Free Discovery Call"
  - **URL:** `https://calendly.com/sarah/discovery`
  - **Priority:** 0
- **Link 2:**
  - **Title:** "Strategy Academy (Course)"
  - **URL:** `https://strategy-academy.com`
  - **Priority:** 1
- **Link 3:**
  - **Title:** "Follow me on Instagram"
  - **URL:** `https://instagram.com/sarah-coach`
  - **Priority:** 2

## 3. Example Lead (Sarah's Leads)
- **ID:** 101
- **Name:** "John Doe"
- **Email:** `john.doe@example.com`
- **Status:** "New"
- **Source:** `saas_profile` (ID: 42)
- **Created At:** `2024-05-01 10:30:00`

## 4. Example Analytics Record
- **ID:** 5001
- **User ID:** 5
- **Event Type:** `click`
- **Target ID:** 77 (Link 1: Discovery Call)
- **Referrer:** `https://t.co/xyz` (Twitter/X)
- **User Agent:** `iPhone; CPU iPhone OS 17_0 like Mac OS X`
- **Created At:** `2024-05-01 10:35:12`

## 5. Elite Use Case: The NFC "Instant Exchange"
- **User:** Marcus Luxury (High-End Real Estate)
- **Profile Slug:** `marcus-luxury`
- **NFC Hardware:** NTAG215 PVC Card with Matte Finish
- **Workflow:**
  1. Marcus taps his card to a prospect's iPhone.
  2. The prospect's phone opens `https://marcus-realestate.com/marcus-luxury?src=nfc`.
  3. The profile loads with Marcus's headshot and a featured "Save My Contact" button.
  4. After 3 seconds, a browser-triggered download for `marcus_contact.vcf` starts.
  5. The prospect taps 'Save' and Marcus is instantly in their address book.
  6. Marcus checks his Analytics Dashboard and sees a 'View' with the 'NFC' tag.
