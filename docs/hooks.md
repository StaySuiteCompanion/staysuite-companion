# Hooks & data

## Actions / filters

| Hook | Type | Notes |
|---|---|---|
| `ssc_loaded` | action | Fires after the plugin container is set up |
| `ssc_group_request_saved` (`$request_id`, `$input`, `$matches`) | action | After a group request is stored + mailed |
| `ssc_quote_payload` (`$input`, `$raw`) | filter | Extend sanitized quote fields |
| `ssc_hotel_search_settings` / `ssc_hero_search_settings` (`$settings`) | filter | Widget field composition |
| `ssc_search_vars` (`$css`, `$submit`) | filter | Override search-bar color mapping |
| `ssc_single_hotel_template` (`$path`) | filter | Override the hotel template file |
| `ssc_room_card_actions` (`$room_id`) | action | Extra buttons per room card |
| `ssc/v1/preview` | REST (POST, `edit_posts`) | `{block, attributes}` → `{html}` editor previews |
| `ssc/v1/settings` | REST (GET/POST, `manage_options`) | Settings read/save |
| `ssc.admin.tabs` | JS filter | Admin tab registry (Pro injects License/AI tabs) |
| `ssc-pro/v1/license`, `/ai-settings`, `/ai-test` | REST (Pro, `manage_options`) | License + AI settings backend |

AJAX: `ssc_group_quote` (quote flow), `ssc_resolve_hotels` (card badges). JS globals: `sscBooking` (`ajaxurl`, `quote_nonce`, `cities`), `sscCards` (`ajaxurl`, `nonce`).

## Stored data

* Post types: `ssc_hotel` (slug `/hotels/`), `ssc_group_request`.
* Room link: `_ssc_hotel_id` on listings; display price `_ssc_original_price`; hotel `_ssc_city/_ssc_address/_ssc_phone/_ssc_featured`; coords reuse theme keys `property_latitude/longitude`.
* Options: `ssc_data_migrated` (upgrade flag). No other options; no transients.

## deliberate extension seams (Pro roadmap)

The free plugin is intentionally thin on hooks today. Before building Pro, add: `ssc_room_card_actions` (extra buttons per room card), `ssc_quote_payload` (extend quote fields server-side), `ssc_strip_fields` (hotel strip composition), `ssc_search_vars` (override color mapping). See the freemium plan for the full split.
