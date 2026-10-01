=== StaySuite Companion for WpRentals ===
Contributors: jktanmay
Tags: wprentals, hotel, booking, group booking, gutenberg blocks
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Hotels, homepage booking blocks and group quotes for the WpRentals theme. No theme files are modified.

== Description ==

StaySuite Companion turns WpRentals into a hotel-ready booking site:

* **Hotels** — a Hotel post type linked to your existing listings (rooms). Each hotel gets a gallery cover, auto-aggregated facilities, a date search strip, per-room availability badges, room cards with the theme's own sliders, and a map.
* **Homepage blocks** — Hero Search (the theme's own search bar on a full-bleed cover), Term Tablets, Listing Carousels, Payment Strip and Group Booking. Also available as shortcodes.
* **Individual / Group switch** — a capsule toggle above the homepage search. Group mode collects party details and sends a combined quote request (stored as Group Requests in wp-admin, emailed to the site admin).
* **Theme-native behavior** — search, datepickers, guest panels, sliders, maps and booking all reuse the theme's own components. Submit buttons and icons track your theme customizer colors automatically.

Built for the [WpRentals](https://themeforest.net/item/wprentals-booking-accommodation-wordpress-theme/12332978) theme. Some features have no effect with other themes active.

== Installation ==

1. Make sure the WpRentals theme is installed and active.
2. Upload `staysuite-companion` to `/wp-content/plugins/` and activate it (or install from Plugins → Add New once listed).
3. Create Hotels under the new Hotels menu and assign rooms to them (room edit screen → Hotel box, Quick Edit, or Hotels → Assign Rooms).
4. Open the **Homepage - StaySuite** page created on activation, edit its blocks, and set it as the static homepage under Settings → Reading. Or build your own page and pick the **StaySuite Homepage** template in Page Attributes.

== Frequently Asked Questions ==

= Do I need WpRentals? =
Yes. The plugin reuses the theme's search, booking, map and slider components and refuses to activate without it.

= Will it survive theme updates? =
Yes. Nothing in the theme is modified; all overrides live in the plugin (templates, styles, scripts).

= Where do group quote requests go? =
Each request is stored as a Group Request post and emailed to the admin address. Manage them under Group Requests in wp-admin.

= How is room availability checked? =
Against the theme's own booking engine (`wpestate_check_booking_valability`), per room and date range. Booking itself stays on the room page.

== Screenshots ==

1. Homepage hero with Individual/Group capsule and pill search bar.
2. Hotel page: gallery cover, facilities, room cards and map.

== Changelog ==

= 0.2.0 =
* Hotel pages: gallery cover, facilities aggregation, theme-native search strip, availability badges, Leaflet map.
* Original (was) price display on hotel room cards.
* Quick Edit hotel assignment for rooms.

= 0.1.0 =
* Initial release: hotels, homepage blocks, group booking.
