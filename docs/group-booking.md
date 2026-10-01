# Group booking

## Individual / Group capsule

On the StaySuite Homepage template only, a capsule toggle mounts above the homepage search bar. Other pages keep the native search untouched.

* **Individual** — the theme search behaves normally.
* **Group** — the search submit routes into the quote flow; a quote form portals below the search (overlapping the cover edge, cover height fixed) with a smooth expand animation, and the page scrolls the capsule just below the header.

Next to a theme search bar the form reads location/dates/guests live from it; standalone (block/shortcode) it renders its own Where/dates/guests fields.

## Quote flow

1. Extras collected: rooms, male/female split, budget range, name/email/phone, requirements.
2. `ssc_group_quote` AJAX → server matching (city, capacity, budget) → suggested stays returned inline.
3. Every request is stored as an `ssc_group_request` post and emailed to the site admin.

Manage requests under Group Requests in wp-admin (status meta box included).

## Related

* `[ssc_group_booking title="…"]` / `ssc/group-booking` block for standalone placement.
* Guest panels default to 2 adults everywhere via the theme's own steppers.
