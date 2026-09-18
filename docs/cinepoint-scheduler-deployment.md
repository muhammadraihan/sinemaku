# Cinepoint daily scheduler deployment

Collector uses ordinary public browser-rendered DOM pagination. It is **not** a PHP-only/shared-host job: the runtime needs Node.js, `playwright-core`, and a Chrome/Chromium executable. Set `CINEPOINT_NODE_BINARY`, `CINEPOINT_BROWSER_SCRIPT`, and `CINEPOINT_BROWSER_CHANNEL` when the host paths are non-default.

## Application setup

```sh
cd /path/to/sinemaku
npm ci
# verify the configured browser runtime
node scripts/cinepoint-daily-browser.cjs > /tmp/cinepoint.json
php artisan config:clear
php artisan view:cache
php artisan route:list --path=backoffice/audience-estimate
```

The browser probe must report `source_total: 21`, 21 entries, and 21 unique IDs. Do not deploy if it reports fewer rows.

## Laravel scheduler

The application schedule runs at 07:00, 12:00, and 18:00 `Asia/Jakarta`, with a ten-minute overlap lock. The host should invoke Laravel every minute:

```cron
* * * * * cd /path/to/sinemaku && /usr/bin/php artisan schedule:run >> /path/to/sinemaku/storage/logs/scheduler.log 2>&1
```

Discover the real PHP binary before installing cron:

```sh
command -v php
php -v
php artisan schedule:list
php artisan schedule:run -v
```

If the host uses a separate PHP binary, replace `/usr/bin/php` with the path printed by `command -v php`. Ensure `storage/` and `bootstrap/cache/` are writable by the web/cron user, and confirm the PHP/application timezone:

```sh
php artisan tinker --execute="dump(config('app.timezone'), now('Asia/Jakarta')->toIso8601String());"
```

## Manual run and diagnosis

```sh
php artisan cinepoint:collect-daily
php artisan schedule:list
php artisan schedule:run -v
php artisan optimize:clear
```

The command exits non-zero on browser failure, incomplete rows, duplicates, database verification failure, stale running recovery, or any other exception. The command message includes the safe cause and retry guidance. A failed attempt never replaces the latest successful snapshot. The admin page at `/backoffice/audience-estimate` shows the latest attempt and manual Sync button; failure feedback uses SweetAlert2 when loaded and an inline fallback otherwise.

Check `storage/logs/laravel.log`, `storage/logs/scheduler.log`, Chrome availability, Node/npm availability, writable directories, and network access to `https://cinepoint.com/`.

## Rollback

Removed legacy TIX/Cinepolis seatmap code was backed up outside the application at:

`/Users/mumuraihan/.hermes/backups/sinemaku-seatmap-tix-20260918`

The old seatmap migrations and database rows are retained in the backup/database; no data-dropping migration is included in this change. Cinepolis distributor/report import code remains untouched.
