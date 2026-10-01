# Customization

## Theme color tracking

Submit buttons, hovers and search icons are **not hardcoded**. They are emitted per load from the theme customizer into CSS variables — recolor the theme and the plugin follows on next load:

| Variable | Source |
|---|---|
| `--ssc-accent` | `wp_estate_main_color` |
| `--ssc-accent-hover` | `wp_estate_hover_button_color` |
| `--ssc-text` / `--ssc-headings` | font / headings colors |
| `--ssc-submit`, `--ssc-submit-hover`, `--ssc-search-icon` | accent + hover (search bars) |

## Body classes

* `ssc-homepage` — StaySuite Homepage template (transparent header, capsule, dividers).
* `ssc-has-hero` — a hero block is present (header search suppressed, hero calendar binding).
* `single-ssc_hotel` — hotel singles (theme map header suppressed).

## Stylesheet

`assets/css/ssc-hotel.css`, cache-busted by content hash, so CSS edits land on normal reload. All rules are `.ssc-`-prefixed; the theme is never overridden globally.
