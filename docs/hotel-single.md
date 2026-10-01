# Hotel single page

Rendered by `templates/single-ssc_hotel.php` (via `template_include`; the theme's map header is suppressed on hotel pages with CSS).

## Sections, top to bottom

1. **Gallery cover** — hotel featured image large left, up to 4 room photos in a 2×2 grid right. Falls back gracefully when images are missing.
2. **Header panel** — hotel name, city/address/phone, "Rooms from X / night" (lowest room `property_price`), room count.
3. **Description** — hotel content.
4. **Facilities** — auto-aggregated union of all rooms' `property_features` terms, A–Z, with the theme's term icons (accent dot fallback). Zero manual entry.
5. **Search strip** — the theme's own Check In / Check Out / Guests widget (no location field), posting back to the hotel page. Guest panel defaults to 2 adults.
6. **Room cards** — one glass card per room: theme slider (arrows, favorite heart, lazy slides), title, Sleeps N (`guest_no`), top amenity chips, price/night, live availability badge, **View & Book** deep link carrying the chosen dates/guests.
7. **Map** — first-party Leaflet map (the theme only initializes listing maps for `estate_property` singles) with a hotel pin. Coords inherit from the first room with lat/lng and are cached onto the hotel; skipped when unknown.

## Availability

`Repository::is_available()` calls the theme engine (`wpestate_check_booking_valability`) per room. Y-m-d input is converted to the theme date format; theme-format input passes through. Badges: green *Available for your dates*, red *Booked for your dates*, hidden until both dates are chosen.
