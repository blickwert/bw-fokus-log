# BW Fokus-Log – project notes

Architecture rules: skill `wp-plugin-architecture` (Blickwert). This file holds what is specific to this repo; if they disagree, this file wins.

## Layout

```
free/                 the plugin (what ends up in the ZIP)
  bw-fokus-log.php    constants, autoloader, Plugin::instance()->boot()
  src/Core/           Plugin, ModuleManager, Module, AbstractModule, Settings, View, App, Admin/AppPage
  src/Modules/        Planner, Coach
  src/Support/        Dates
  templates/planner/  app.php (mount point, theme-overridable: <theme>/bw-fokus-log/planner/app.php)
  blocks/planner/     block.json (server-side block)
  languages/          .pot, de_DE .po/.mo, JSON for the app (generated)
  build/              compiled app (not in Git)
app/                  TypeScript source of the browser app (built with @wordpress/scripts)
tests/Unit/           PHPUnit + Brain Monkey
scripts/              build-zip.sh, i18n.py (+ i18n/de_DE.json = German translations)
```

Prefix `bw_fokus_log_`, namespace `BW\FokusLog\`, text domain `bw-fokus-log`, REST `bw-fokus-log/v1`, CSS classes `bwfl-`.
There is no Pro plugin yet (`pro/` is not created).

## Modules

| ID | Does | Hooks it adds |
|---|---|---|
| `planner` | Day plan as text, REST for day/history/settings, history numbers, break length, block + shortcode `[bw_fokus_log]` | action `bw_fokus_log_day_saved( $user_id, $date, $stats )`, filter `bw_fokus_log_default_pause` |
| `coach` | Coach sayings (run, ticking off, all done), editable by administrators, REST `POST /coach` | – (uses `bw_fokus_log_app_config`) |

Core hooks: `bw_fokus_log_register_modules`, `bw_fokus_log_module_allowed`, `bw_fokus_log_app_config`, `bw_fokus_log_template`, action `bw_fokus_log_app_mount`.

## Browser app

JS modules talk to each other only through `@wordpress/hooks` filters (`app/lib/extend.ts`):
`bw-fokus-log.routes`, `.providers`, `.settings-panels`, `.run-quote`, `.celebration`.
The Planner works without the Coach and the other way round.

## Decisions that differ from the skill (on purpose)

- The browser app is also used on the website (block/shortcode), not only in the admin, because it is a personal timer tool with live state. The skill asks for server-rendered pages there.
- Plain `<input>`/`<textarea>`/`<button>` with `style.css` instead of `@wordpress/components`, to stay close to the design mockup and to load nothing extra on the website.
- Accent colour is teal `#0F766E` (variable `--bwfl-accent`, themeable) as in the mockup, not the admin colour scheme.
- System fonts instead of the mockup's Google Fonts (privacy).
- The table name `{prefix}fokus_log_days` is kept from version 1 so existing data survives. Options and user meta use the new prefix; the old `fokus_log_*` ones are not migrated.

## Commands

```bash
composer --working-dir=free install   # dev tools (vendor in free/vendor)
composer --working-dir=free lint      # phpcs, WordPress standard
composer --working-dir=free test      # phpunit
npm install
npm run typecheck           # tsc
npm run build               # app -> free/build
python3 scripts/i18n.py     # translations
bash scripts/build-zip.sh   # installable ZIP
```

## Before a commit

`composer --working-dir=free lint`, `… test`, `npm run typecheck`, `npm run build` are green; new hooks documented here; on release raise the version in the plugin header, `BW_FOKUS_LOG_VERSION` and `free/readme.txt`, then `git tag vX.Y.Z`.
