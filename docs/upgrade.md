# Upgrade & migration

## `vs_*` → `ssc_*` rename (0.2.0)

Version 0.2.0 renamed the plugin from *Varsity Surfers Companion* to *StaySuite Companion for WP Rentals*. A one-time, idempotent routine (`Installer::maybe_migrate()`, flag `ssc_data_migrated`) runs on activation and on `init` until done:

* Post types `vs_hotel` → `ssc_hotel`, `vs_group_request` → `ssc_group_request` (IDs preserved).
* All `_vsc_*` postmeta keys → `_ssc_*`.
* `_wp_page_template` value `vs-homepage` → `ssc-homepage`.
* Post content: `<!-- wp:vs/…` → `<!-- wp:ssc/…`, `[vs_…` shortcodes → `[ssc_…`.
* Rewrite rules flushed.

Code, file, handle, CSS/JS and REST renames need no migration (no stored references). If you customized templates by copying them, re-copy from the new filenames (`single-ssc_hotel.php`, `page-ssc-homepage.php`).
