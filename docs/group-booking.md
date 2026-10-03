# Group booking

## Individual / Group capsule

On the StaySuite Homepage template only, a capsule toggle mounts above the homepage search bar. Other pages keep the native search untouched.

* **Individual** — the theme search behaves normally.
* **Group** — the search submit routes into the quote flow; a quote form portals below the search (overlapping the cover edge, cover height fixed) with a smooth expand animation, and the page scrolls the capsule just below the header.

Next to a theme search bar the form reads location/dates/guests live from it; standalone (block/shortcode) it renders its own Where/dates/guests fields.

## Quote flow

1. Extras collected: rooms, male/female split, budget range, name/email/phone, requirements.
2. `ssc_group_quote` AJAX → honeypot check → strict validation (name, email, Y-m-d dates with check-out after check-in and no past check-in, caps on lengths and party sizes) → per-IP rate limit (5 per 10 minutes, see `ssc_quote_rate_limit`) → server matching (city, capacity, budget) → suggested stays returned inline.
3. Every request is stored as an `ssc_group_request` post and emailed to the site admin, with a confirmation email to the visitor. The admin mail carries a `Reply-To` with the visitor's address.

Stale nonces (cached pages) return an `ssc_nonce_expired` code; the form fetches a fresh nonce from `ssc_quote_nonce` and retries once.

Manage requests under Group Requests in wp-admin (status meta box included). Requests hold personal data, so the post type is administrator-only (`manage_options` on every capability).

## Related

* `[ssc_group_booking title="…"]` / `ssc/group-booking` block for standalone placement.
* Guest panels default to 2 adults everywhere via the theme's own steppers.
