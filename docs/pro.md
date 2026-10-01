# StaySuite Pro architecture

Separate plugin (`staysuite-companion-pro`), never bundled with free. Contract:

## Gates

* `Requires Plugins: staysuite-companion` header (WP 6.5+) **plus** runtime gate: boots only when `SSC_VERSION` exists and is `>= 0.2.0`, else admin notice. Free boots at `plugins_loaded:5`, Pro at `:20`.
* Free stays fully functional with Pro absent or unlicensed.

## License & updates

* `License`: key in `ssc_pro_license_key`, status in `ssc_pro_license_status`, settings under Hotels → Pro License. `verify()` is a stub — connect EDD/Freemius before selling. `ssc_pro_license_valid` filter allows overrides (tests, staging, lifetime deals). No license code ever ships in free (wp.org rule).
* `Updater`: side-effect-free stub. Wire `pre_set_site_transient_update_plugins` + `plugins_api` to the store endpoint when ready; until then manual-zip distribution.

## Integrations

`Integrations` maps 1:1 to free seams (all inert until implemented):

| Free seam | Pro feature |
|---|---|
| `ssc_room_card_actions` | Add Room button → multi-room selection |
| `ssc_quote_payload` + `ssc_group_request_saved` | Add-ons/deposits; persist `_ssc_pro_*` meta |
| `ssc_hotel_search_settings` / `ssc_hero_search_settings` | Extra strip fields |
| `ssc_search_vars` | Alternate color mapping |
| `ssc_single_hotel_template` | Full override when hooks run out |

## Data rules

* Pro writes only `_ssc_pro_*` postmeta and its own tables (ICS, analytics later). Never alters free schema — deactivating Pro degrades gracefully.
* Feature order: multi-room selection → quote pipeline → seasonal pricing → OTA sync → add-ons → analytics (see freemium plan).
