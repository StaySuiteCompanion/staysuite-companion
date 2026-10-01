# StaySuite Companion for WP Rentals

Hotels, homepage booking blocks and group quotes for the [WP Rentals](https://themeforest.net/item/wprentals-booking-accommodation-wordpress-theme/12332978) theme — without touching theme files.

![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue) ![Requires PHP](https://img.shields.io/badge/php-%3E%3D7.4-777) ![WP](https://img.shields.io/badge/wordpress-%3E%3D6.0-21759B)

## What it does

| Area | Details |
|---|---|
| **Hotels** | `ssc_hotel` post type linked to listings (rooms): gallery cover, auto-aggregated facilities, date strip, availability badges, glass room cards with theme sliders, map |
| **Homepage blocks** | `ssc/hero-search`, `ssc/term-tablets`, `ssc/listing-carousel`, `ssc/payment-strip`, `ssc/group-booking` (+ `ssc_*` shortcodes) |
| **Group booking** | Individual/Group capsule on the homepage search; group mode sends a combined quote (stored + emailed) |
| **Theme-native** | Reuses the theme's search, datepickers, guest panels, sliders and maps; colors track the customizer |

Full user docs live in [`docs/`](docs/README.md). The wp.org listing file is [`readme.txt`](readme.txt).

## Develop

```bash
npm install
npm run build   # wp-scripts → assets/build
```

PHP follows WordPress coding standards with PHPDoc everywhere; JS is React via `@wordpress/scripts`. No theme file is ever modified — overrides ship as plugin templates, styles and scripts.

## Release

```bash
bin/export.sh [version]
```

Builds a lightweight wp.org zip (production files only — no `src`, `node_modules`, docs or configs) into `dist/`. See [`docs/export.md`](docs/export.md).

## License

GPL-2.0-or-later. © Tanmay Kirtania — [jktanmay.com](https://jktanmay.com)
