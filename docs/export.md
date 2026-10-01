# Release export

`bin/export.sh [version]` builds `dist/staysuite-companion-{version}.zip` (version defaults to the plugin header).

## Ships (production only)

`staysuite-companion.php`, `readme.txt`, `includes/`, `templates/`, `languages/`, `vendor/`, `assets/build|css|js`.

## Excluded

`src/`, `node_modules/`, `docs/`, `README.md`, `package*.json`, `composer.json`, `webpack.config.js`, `bin/`, `dist/`, maps and `.DS_Store`.

## Process

1. `npm run build` (fresh bundles), lint PHP.
2. Bump `Version` header + `readme.txt` Stable tag + changelog.
3. `bin/export.sh` and sanity-check the zip listing.
4. Upload to wp.org (SVN tag) or attach to the GitHub release.
