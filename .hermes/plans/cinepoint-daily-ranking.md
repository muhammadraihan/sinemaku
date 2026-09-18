# Cinepoint Daily Ranking vertical slice

- Add independent `cinepoint_daily_snapshots` and `cinepoint_daily_entries` tables. Snapshot rows retain success/partial/failure history and errors; entries belong only to successful/partial snapshots.
- Parse Cinepoint Angular `ng-state`, validate pagination and daily period independently from weekly metadata, deduplicate movie IDs, and reject malformed rows.
- Collect all publicly available pages when possible. Mark any count below source `total` as partial and retain the error. Never replace the latest successful snapshot on a failed run.
- Add `cinepoint:collect-daily` with cache lock and HTTP timeouts. Keep the runtime path PHP/Guzzle-compatible; browser exploration is verification only.
- Reuse authenticated `/backoffice/audience-estimate`; add stored JSON and explicit POST sync endpoints via a dedicated controller.
- Replace the ranking body with Cinepoint cards/table: poster, title, daily/total admissions, source attribution, daily period, last sync, completeness, next configured runs, and scheduler heartbeat status.
- Schedule collection at 07:00, 12:00, and 18:00 Asia/Jakarta with overlap protection; remove old TIX/Cinepolis discovery scheduling without deleting legacy code/data.
- Verify parser edge cases, persistence/failure retention, auth routes, controller payload, Blade compilation, migration, live collection, and git diff integrity.
