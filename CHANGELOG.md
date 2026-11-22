# Changelog

All notable changes to this project are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) · Versioning: [SemVer](https://semver.org/).

## [Unreleased]

### Changed
- Author is now Cédric Malvache (with author link to the GitHub profile).

## [2.1.1] – 2026-11-22

### Added
- Automatic updates from GitHub releases through the WordPress `Update URI` header and the `update_plugins_github.com` filter: update notices, one-click updates, auto-updates and release notes in *View details*.
- *Check for updates* link on the Plugins screen.
- `festive_snowfall_update_repository` filter (for forks).

### Security
- Only the `festive-snowfall.zip` asset of this repository's own, non-draft, non-prerelease releases is accepted; release notes are escaped. API failures are cached for one hour.

## [2.1.0] – 2026-11-21

### Added
- Date-based **schedules** (up to 20): each has its own dates, flakes, colours, speed, quantity, size and banner. The first matching schedule in the list wins.
- Schedule cards with *Move up*, *Move down*, *Duplicate*, *Delete*; *Today* and *Testing* badges; live preview of the schedule being edited.
- Per-schedule testing mode (replaces the global admin preview).
- 10 new Christmas banners: candy canes, golden stars, gingerbread men, mistletoe, gold tinsel, hanging gifts, Christmas trees, snowmen, red ribbon and bows, poinsettias.
- `festive_snowfall_schedule` filter.

### Changed
- Code split into `includes/` classes (core, admin, period).
- Banner position and CSS selector are now global settings.
- Settings from 2.0 are migrated automatically into a first schedule.

## [2.0.0] – 2026-11-21

### Added
- Settings page (*Settings → Festive Snowfall*) with live preview.
- Configurable start and end dates, yearly repeat, admin-only testing mode.
- Six flake shapes (mixable), up to six colours, speed (1–10), quantity per device and size range.
- Five banner styles (holly, pine, light string, baubles, icicles), height and position (top, fixed, CSS selector).
- `festive_snowfall_should_display` filter.
- French translation, uninstall cleanup (multisite aware), automated tests and CI.

### Changed
- Renamed from *CGVL – Décor de Noël* to *Festive Snowfall* (slug `festive-snowfall`).
- Period is now checked server-side using the site time zone; assets are only enqueued when needed.
- Assets moved to external, versioned CSS/JS files loaded with `defer`.

## [1.0.0]

### Added
- Initial version: snowflakes and holly garland from 1 December to 6 January.
