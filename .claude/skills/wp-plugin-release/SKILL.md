---
name: wp-plugin-release
description: Version control and release workflow for WordPress plugins and themes - what to commit vs gitignore, where the version number lives, how to build a minimal installable zip, how to tag and publish, and how to ship to the wordpress.org SVN. Use this whenever the user mentions releasing, shipping, publishing, versioning, bumping a version, tagging, cutting a build, preparing a plugin zip, updating Stable tag, pushing to wp.org/SVN, hotfixing a released version, or keeping a free plugin and its premium add-on in sync - even if they phrase it as "ship it", "push it live", "make the zip", or "what should I commit". Also use it before answering questions about what belongs in a distributed WordPress plugin folder.
---

# WordPress plugin release & version control

WordPress plugins have two audiences with conflicting needs. The repository is a developer tool: it wants `node_modules`, tests, config and a real commit history. The distributed zip is an installation artifact: it wants runtime PHP, compiled assets and nothing else. Nearly every WordPress release mistake comes from mixing those two, so this skill keeps them strictly separate.

## The split, in one rule

**Git tracks sources. The zip ships the runtime.** Anything that is only needed to *build* the plugin (`src/`, `node_modules/`, `phpcs.xml`, `webpack.config.js`, `package.json`, `composer.json`) stays in the repo and out of the zip. Anything needed to *run* it (compiled `build/`, `vendor/autoload.php`, templates, translations) ships.

Two mechanisms enforce it, and you want both:

| Mechanism | Purpose |
|---|---|
| Allowlist in the build script (`PRODUCTION_PATHS`) | Decides what is in the zip. Fails loudly if a dev path appears. |
| `.gitattributes` with `export-ignore` | Keeps GitHub's source zip and `git archive` lean too. |

An allowlist is safer than a denylist: a new dev directory added later is excluded by default instead of silently shipping. When a new runtime folder appears (say `assets/images/`), add it to both places in the same commit, and mention it in the changelog.

## What git should and should not track

Track: plugin PHP, templates, `assets/` (including compiled `build/`), `readme.txt`, `languages/*.po/.mo`, `LICENSE`, docs, build scripts, `.phpcs.xml`-style configs.

Ignore: `node_modules/`, `dist/`, `vendor/`, composer/npm lockfiles only if you deliberately do not pin dependencies, `.DS_Store`, `*.log`, `.env`, source maps.

Two judgement calls that trip people up:

- **Compiled assets.** WordPress.org reviewers expect the built CSS/JS in the zip. Either commit `build/` output, or build it during packaging. Both work; pick one and stay consistent, because a build that happens only on your machine produces broken releases for everyone else.
- **Lockfiles.** Commit `composer.lock` and `package-lock.json` when you ship `vendor/` in the zip — reproducibility is the whole point of shipping dependencies. Ignore them only if the release pipeline always runs a fresh `composer install --no-dev`.

## Where the version number lives

Never in one place. WordPress reads the plugin header; your own update checker reads a constant; wordpress.org reads `Stable tag:`. These three drift apart within a week if nobody syncs them.

```
plugin.php        * Version: 1.2.0          ← wp.org + Updates screen
plugin.php        define('MY_VERSION', …)  ← your updater compares against it
readme.txt        Stable tag: 1.2.0        ← wp.org serves this exact tag
package.json      "version": "1.2.0"       ← tooling, but drifts if ignored
```

Have one command write all of them, and make the release build **fail** if they disagree. A zip whose header says 1.2.0 while Stable tag says 1.1.0 will be silently rejected or, worse, installed as the wrong version on a user's site.

`Stable tag` may only move forward. wordpress.org refuses a re-upload of an existing version, so a fixed build always means a new version number - a hotfix is `1.2.1`, never a re-upload of `1.2.0`.

## Driving it from one command

A checklist is where omissions happen, so the whole sequence belongs in a script, and the bump should be a flag rather than something the user types:

```bash
release.sh              # patch: 1.0.4 -> 1.0.5  (the common case)
release.sh --minor      # 1.0.4 -> 1.1.0
release.sh --major      # 1.0.4 -> 2.0.0
release.sh --set 1.2.3  # exact, for the rare override
```

Default to **patch** for no-argument runs: it is what most releases are, and a default that guesses wrong is worse than one that refuses. Make the release the *only* command that writes a version - a build script that bumps versions produces artifacts claiming releases that never happened.

The script must write every carrier in one pass and then verify they agree; a `--dry-run` that prints each planned edit makes the whole thing reviewable before anything irreversible happens.

## Free plugin and premium add-on

When the premium part is a separate plugin in its own repository, it declares a dependency in two places: the `Requires Plugins:` header and a runtime version gate (`version_compare(defined('FREE_VERSION'), MIN_FREE_VERSION, '<')` plus an admin notice).

That gate constant is a **floor, not a mirror** of the free version. Raise it only when the add-on starts using a hook that exists solely in a newer free release - raising it automatically on every free patch would force every add-on customer to update the free plugin for no reason. When it does move, it must move everywhere at the same time: the gate constant, the notice text, both readmes, and the architecture doc that describes the gate. Because the add-on lives in another repository, a release that touches it should print the files it changed so they get committed and released separately, instead of leaving a silent uncommitted change in a neighbouring checkout.

Release the free plugin first, then the add-on - never the other way round, or you publish an add-on that the current free plugin does not satisfy.

## Choosing a version

SemVer, with the reading a site owner would give it:

- **patch** — bug fixes, no behaviour change, no data migration. Users can update without reading anything.
- **minor** — new blocks, settings, hooks, admin screens. Safe defaults, nothing removed.
- **major** — removed hooks, renamed meta keys, changed CPT slugs, PHP/WP minimum bumps, anything that needs a migration routine or an upgrade notice.

If in doubt after shipping: adding is minor, breaking is major.

## Release flow

Prefer a script over a checklist — the steps are the same every time and a human checklist is where the omission happens. Most plugins ship something equivalent to:

```bash
bin/build.sh             # minimal production zip → dist/
bin/release.sh patch     # bump, changelog, zip, commit, tag v1.2.1, push, GitHub release
```

The release command should, in order:

1. **Preflight** - refuse a dirty working tree, refuse a version that is not newer, refuse a tag that exists locally or on origin. A release cut from uncommitted work cannot be reproduced.
2. **Sync the version** into every file that carries one.
3. **Insert the changelog entry** (see the `wp-changelog` skill for how to write it).
4. **Build** the zip from a clean production dependency install.
5. **Verify the zip** - unpack it and confirm it contains the entry file, no dev paths, no source maps.
6. **Commit, tag, push**, then attach the zip to the GitHub release.

Offer `--dry-run` that prints every step and writes nothing, and `--no-push` for when someone wants to review the commit first.

## Production dependencies in the zip

If the plugin loads `vendor/autoload.php`, the zip must contain a production vendor tree. Install it **in the staging directory**, not in the working copy:

```bash
cp composer.json composer.lock "$stage/"
(cd "$stage" && composer install --no-dev --optimize-autoloader)
```

Running it in the stage keeps `phpcs` working on the developer's machine afterwards. If you use `classmap` autoloading, remember it needs the real source tree present when the autoloader is generated - so generate it after staging sources, never before.

## Traps worth knowing before you hit them

- **`NODE_ENV=production` in the shell** makes `npm install` skip devDependencies, so `@wordpress/scripts` is missing and `npm run build` fails with a confusing "command not found". Force `NODE_ENV=development` for the install step.
- **Autoload drift.** A new class that is not in the generated classmap loads on your machine (dev autoloader) and fatals on a user's site (shipped autoloader). Dump with `-o` after staging.
- **Source maps** leak your whole `src/` tree inside the zip and roughly double its size.
- **`.DS_Store`** ships from a macOS machine and makes plugin directory listings ugly on the plugin page.
- **`Requires at least` / `Requires PHP`** in the header must match the code you actually tested, or Plugin Check flags the release.
- **Two repos, one product.** A premium add-on is usually a separate plugin and its own repository. It declares a dependency on the free plugin (`Requires Plugins:` header plus a runtime version gate). When a free release changes a hook the add-on uses, patch both, then note the required minimum in both readmes before publishing either.

## wordpress.org publish

The SVN flow differs from a normal release and is worth doing in this exact order:

```bash
svn co https://plugins.svn.wordpress.org/my-plugin /tmp/my-plugin-svn
cp dist/my-plugin-1.2.1.zip /tmp/my-plugin-svn/my-plugin/
cd /tmp/my-plugin-svn
svn add my-plugin/1.2.1.zip
svn ci -m "Add 1.2.1 zip"
svn cp my-plugin/1.2.1.zip my-plugin/trunk/my-plugin-1.2.1.zip
svn ci -m "Release 1.2.1 to trunk"
svn up
svn cp my-plugin/trunk my-plugin/tags/1.2.1     # tag the *tag folder*, not the zip
svn ci -m "Tag 1.2.1"
```

Tagging `trunk` rather than the zip is what makes old installs keep working: an installed plugin's update check reads `tags/{version}`, and a tag pointing at a zip would break it. The `/assets` directory at the SVN root is separate and holds screenshots and banners.

Readme formatting is machine-parsed, so the structure matters as much as the wording:

```
=== Plugin Name ===
Contributors: username
Tags: tag1, tag2, tag3
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
== Installation ==
== Frequently Asked Questions ==
== Screenshots ==
== Changelog ==
```

## Before you ship

Run the same checklist every time; it is short because the earlier steps already did the work:

- `unzip -l dist/*.zip` shows only runtime files - no `src/`, `node_modules/`, `*.map`, `.DS_Store`, configs.
- The zip unpacks into a single `<slug>/` directory, or you are uploading loose files and the installer will scatter them into `wp-content/`.
- Version numbers agree across header, constant, `Stable tag` and `package.json`.
- `readme.txt` short description fits 150 characters and does not shout.
- Plugin Check (or `wp-env` smoke test) is clean, with `WP_DEBUG` on, on a fresh install.
- Upgrade path tested: install the previous release, then upgrade to this one, and confirm settings and content survive.
- Tag and GitHub release exist with the zip attached; wp.org SVN is updated last, once you are sure.

## Working with the user

When someone asks to release, confirm the version bump and the changelog notes before doing anything irreversible - pushing a tag is easy to undo, publishing to wordpress.org is not. Show the zip listing in the summary; it is the single most useful proof that the release is clean.