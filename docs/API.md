# SaaS Developer & API Documentation

## 1. Internal REST API
The system provides a lightweight REST API for tracking and future integrations.

### Analytics Tracking
- **Endpoint:** `POST /wp-json/saas/v1/track`
- **Description:** Records a view, click, or conversion event.
- **Payload:**
```json
{
  "event": "click",
  "target_id": 123
}
```
- **Response:** `200 OK` on success.

### Payment Webhooks
- **Endpoint:** `POST /wp-json/saas/v1/webhook`
- **Description:** Receives payment confirmation from Stripe/PayPal.
- **Payload (Example):**
```json
{
  "user_id": 1,
  "status": "succeeded",
  "plan": "pro"
}
```

---

## 2. Lead Automation (Webhooks)
When a lead is captured, the system can trigger an external webhook (e.g., Zapier, Make).

**Data Format sent to external URL:**
```json
{
  "name": "Lead Name",
  "email": "lead@email.com",
  "profile": 456
}
```

---

## 3. Data Schema Highlights
- **Post Types:** `saas_profile`, `saas_link`, `saas_lead`.
- **Custom Table:** `wp_saas_analytics` for high-performance event logging.

---

## 4. Shortcodes
- `[saas_dashboard]`: Main user management interface.
- `[saas_login_form]`: Minimalist AJAX login form.
- `[saas_register_form]`: Conversion-optimized signup form.
