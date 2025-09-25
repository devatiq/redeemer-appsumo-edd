=== Redeemer for AppSumo + EDD ===
Contributors: nexiby
Tags: edd, licensing, appsumo, redemption
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.0
License: GPL2+

Redeem AppSumo promo codes and auto-issue EDD Software Licensing licenses.

== Description ==
This plugin exposes a secure REST endpoint to validate an AppSumo code (locally or via API)
and then creates a $0 completed EDD payment for a configured Download/Price ID so that
EDD Software Licensing generates the license automatically.

== Usage ==
1) Activate plugin.
2) Settings → AppSumo Redeemer:
   - Set Webhook/REST Secret.
   - Set EDD Download ID (e.g., 2067).
   - Set Allowed Price IDs (e.g., 1,2).
   - (Optional) Seed codes in “Seed Codes” as CODE|PRICE_ID (one per line).
   - (Optional) Enable “Infer Tier from Code Prefix” (AS-1-..., AS-2-...).
3) REST endpoint:
   POST /wp-json/rae/v1/redeem
   Headers: Authorization: Bearer YOUR_SECRET
   Body JSON:
     {
       "email": "buyer@example.com",
       "code": "AS-1-ABCDE12345",
       "price_id": 1,
       "name": "Buyer Name"
     }

Returns: { ok, payment_id, price_id, licenses[] }
