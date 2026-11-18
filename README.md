# BW Fokus-Log

A daily planner against procrastination. Write your day as a text list; every line becomes a tappable to-do with a timer. Between to-dos there is a break with a fixed end, a coach motivates you, and the history shows how you get more done over the weeks.

German UI is included (`de_DE`); the source language is English.

## Features

- **Day plan as text**: one line per to-do, starting with `- `.
- **Tap** to start or pause the timer. Starting another to-do pauses the running one.
- **Tick** (shown on hover, always on touch devices) ticks a to-do off, with or without timer. Clicking the tick of a finished to-do reopens it.
- **Break between to-dos** with countdown, sound at the end and a "Start next" button. The length is set in the app.
- **Coach**: a new saying under the running to-do at every start; a party with confetti when you tick off. Administrators can change the sayings (placeholders `{t}` to-do, `{d}` duration, `{n}` done, `{total}` all).
- **Saved like a text file** (copy or download as `.txt`):
  ```
  - Go for a walk {08:30|09:05|00:35}
  - Make the bed {09:10|09:16|00:06}
  - Open the mail {09:20||00:04}
  - Vacuum
  ```
  `{start|end|duration}`: the duration is pure timer time (hh:mm), without breaks. An empty end means started but not finished.
- **Get open ones from yesterday**.
- **History**: done to-dos and focus time of the last 14 days, compared with the week before.
- The tab title shows the running timer or break.
- Every signed-in user sees only their own data.

## Use

- **Admin**: menu item **Fokus-Log**.
- **Website**: add the block **Fokus-Log** or the shortcode `[bw_fokus_log]` to a (preferably private) page. Visitors who are not signed in see a login link.
- Shortcut in the plan editor: **Ctrl/Cmd + Enter** applies the plan.

## Install

Download `bw-fokus-log-<version>.zip` and upload it under *Plugins → Add New → Upload Plugin*. The table `{prefix}fokus_log_days` is created on activation.

From the repo, for development:

```bash
composer --working-dir=free install && npm install && npm run build
ln -s "$PWD/free" /path/to/wp-content/plugins/bw-fokus-log
```

## Development

```bash
composer --working-dir=free lint   # WordPress coding standards
composer --working-dir=free test   # PHPUnit + Brain Monkey
npm run typecheck
npm run build      # app -> free/build (not in Git)
python3 scripts/i18n.py
bash scripts/build-zip.sh
```

Architecture and decisions: see [CLAUDE.md](CLAUDE.md).

### REST (signed in, nonce `wp_rest`)

| Method | Path | Content |
|---|---|---|
| GET | `/wp-json/bw-fokus-log/v1/day?date=YYYY-MM-DD` | Text of the day |
| POST | `/day` `{date, content}` | Saves the text; done count and focus time are derived from it |
| GET | `/history?today=YYYY-MM-DD&days=14` | One entry per day (missing days = 0) |
| POST | `/settings` `{pause}` | Break in minutes (per user) |
| POST | `/coach` `{run, titles, done, allDone}` | Coach sayings (administrators) |

### How it saves

On every action (start, pause, tick, apply plan), every minute while a timer runs and when the tab closes. A local copy in the browser (`localStorage`) keeps your work if the server is briefly unreachable and lets a running timer survive a reload. The date is the browser's local date; after midnight, reload the page.

## Requirements

WordPress 6.4+, PHP 8.3+.

## License

GPL-2.0-or-later
