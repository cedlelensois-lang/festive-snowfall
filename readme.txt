=== Festive Snowfall ===
Contributors: cgvlmerville
Tags: christmas, snow, snowflakes, holiday, banner
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Falling snowflakes and 15 festive banners, with date-based schedules so each period has its own style.

== Description ==

Festive Snowfall adds a light holiday decoration to your site:

* **Schedules**: up to 20 periods, each with its own dates and style (e.g. holly during Advent, twinkling lights on Christmas Eve, golden stars for New Year). The first matching schedule in the list wins.
* Dates repeated every year or not; periods crossing New Year are supported.
* **6 flake shapes** to mix, **up to 6 colours**, adjustable **speed**, quantity (desktop / mobile) and size.
* **15 Christmas banners**: holly, pine, twinkling lights, baubles, icicles, candy canes, golden stars, gingerbread men, mistletoe, gold tinsel, gifts, Christmas trees, snowmen, red ribbon, poinsettias.
* Banner at the top of the page, fixed at the top of the screen, or inside a theme element (CSS selector).
* Live preview, duplicate / reorder schedules, testing mode for administrators.
* Accessible: respects reduced-motion preferences, decorative elements hidden from screen readers.
* No external requests, no tracking. Assets load only when needed.

French translation included.

== Installation ==

1. Upload the plugin through *Plugins > Add New > Upload Plugin*, or copy the `festive-snowfall` folder to `wp-content/plugins/`.
2. Activate it.
3. Configure it under *Settings > Festive Snowfall*.

== Frequently Asked Questions ==

= Two schedules overlap. Which one is shown? =

The first one in the list. Put short periods (e.g. 24–25 December) above longer ones and use *Move up / Move down*.

= The style does not change on the expected date =

Schedules are evaluated on the server. If you use a page cache or CDN, purge it on each change date. Also check the time zone in *Settings > General*.

= How can I test a schedule outside its dates? =

Tick *Testing* on that schedule. Only administrators will see it.

= The banner is not where I want it =

In *General*, choose *Inside a theme element* and enter the CSS selector of your header (e.g. `#header` or `.site-header`).

= Can I hide it on some pages? =

Use the `festive_snowfall_should_display` or `festive_snowfall_schedule` filters (see the GitHub README).

== Screenshots ==

1. Banners and snowflakes.
2. The 15 banner styles.
3. Settings page with schedules and live preview.

== Changelog ==

= 2.1.0 =
* New: date-based schedules, each with its own flakes, colours, speed and banner.
* New: 10 additional Christmas banners (15 in total).
* New: duplicate and reorder schedules, per-schedule testing mode.
* New: `festive_snowfall_schedule` filter.
* Settings from 2.0 are migrated automatically.

= 2.0.0 =
* Renamed to Festive Snowfall, settings page with live preview, configurable dates, flakes, colours, speed and banners.

= 1.0.0 =
* First version.

== Upgrade Notice ==

= 2.1.0 =
Adds date-based schedules and 10 new banners. Existing settings become your first schedule.
