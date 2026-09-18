# TIX ID Audience Estimate — Detailed Implementation Blueprint

> **Status:** Final implementation blueprint. Implement file-by-file in the order below. Do not redesign schema, routes, payloads, page structure, or terminology during coding unless a verified TIX ID response makes a field technically impossible.

## 1. Final Product Definition

Build an authenticated Sinemaku backoffice module that:

1. uses **TIX ID as the primary discovery aggregator** for XXI, CGV, Cinepolis, FLIX, and SAMS Studio;
2. keeps the existing Cinepolis direct worker as fallback/audit;
3. captures read-only seat-layout snapshots only after a safety probe proves that loading layout does not select, hold, reserve, order, or pay;
4. calculates **Estimated Seat Uptake** from observed seat status;
5. combines the same film/showtime across sources without double counting;
6. presents a national ranking, film drill-down, cinema/showtime breakdown, source health, and reconciliation with official distributor reports;
7. never labels the estimate as official admissions or confirmed ticket sales.

Final terminology:

- Primary metric: **Estimated Seat Uptake**
- Secondary metric: **Estimated Occupancy**
- Method label: **Public Seat-map Signal**
- Official comparator: **Distributor Report Admissions**
- Never use `Daily Est. Adm.` in the UI.

## 2. Non-negotiable Safety Contract

The implementation must:

- open only public TIX ID discovery/movie/cinema pages;
- let the official frontend obtain its own application token;
- observe relevant JSON responses inside the temporary browser context;
- discard browser context after every collection batch;
- never persist/log/return authorization headers, cookies, access tokens, refresh tokens, client secrets, OTPs, phone numbers, or account credentials;
- never click a seat;
- never call order-summary, select-payment, payment, checkout, refund, ticket purchase, or seat-hold actions;
- never bypass login, OTP, CAPTCHA, bot protection, rate limit, or device attestation;
- abort layout collection if any mutating request is detected;
- respect the published TIX ID crawl delay of at least 10 seconds between navigations;
- retain previous valid data when a source becomes blocked; never replace it with zero.

The worker must detect these request substrings as unsafe and abort the layout probe:

```text
/order
/payment
/checkout
/select-payment
/order-summary
/reserve
/hold
/purchase
/transaction
```

Allowed read-only endpoint patterns:

```text
/v1/movies/{id}
/v1/schedules/date
/v1/schedules/movies/{schedule_id}
/v1/schedules/theaters/{theater_id}
/v1/movies/xxi/layout
/v1/movies/cgv/layout
/v1/movies/cinepolis/layout
/v1/movies/flix/layout
/v1/movies/sams/layout
```

## 3. Final Database Design

Do not modify the already-applied migration `database/migrations/2026_09_16_113022_create_seatmap_monitoring_tables.php`. Create one additive migration:

```text
database/migrations/2026_09_16_140000_add_tix_aggregation_fields_to_seatmap_tables.php
```

### 3.1 `seatmap_sources` additions

```php
$table->string('method', 40)->default('browser_public')->after('provider');
$table->string('health_status', 40)->default('pending')->after('status');
$table->timestamp('last_discovery_at')->nullable()->after('health_status');
$table->timestamp('last_snapshot_at')->nullable()->after('last_discovery_at');
$table->timestamp('backoff_until')->nullable()->after('last_snapshot_at');
$table->text('last_error_category')->nullable()->after('backoff_until');
$table->text('last_error_message')->nullable()->after('last_error_category');
$table->json('observed_chains')->nullable()->after('last_error_message');
```

Allowed `health_status` values:

```text
pending
discovery_active
layout_verified_read_only
layout_pending_probe
requires_authentication
blocked_by_source
rate_limited
backing_off
disabled_by_policy
error
```

### 3.2 `seatmap_showtimes` additions

```php
$table->string('chain', 40)->nullable()->after('source_uuid');
$table->string('external_movie_id')->nullable()->after('external_showtime_id');
$table->string('external_schedule_id')->nullable()->after('external_movie_id');
$table->string('external_cinema_id')->nullable()->after('external_schedule_id');
$table->string('canonical_key', 64)->nullable()->after('external_cinema_id');
$table->string('normalized_film_name')->nullable()->after('film_name');
$table->string('normalized_cinema_name')->nullable()->after('cinema_name');
$table->string('timezone', 60)->default('Asia/Jakarta')->after('show_time');
$table->json('source_metadata')->nullable()->after('booking_url');
$table->boolean('snapshot_eligible')->default(false)->after('status');
```

Indexes:

```php
$table->index(['canonical_key', 'show_date']);
$table->index(['chain', 'show_date', 'status']);
$table->index(['snapshot_eligible', 'show_date', 'show_time']);
$table->unique(['source_uuid', 'external_showtime_id'], 'seatmap_source_showtime_unique');
```

Before adding the unique index, query duplicates. If duplicates exist, retain the newest row, move snapshots to it, and archive/delete only duplicate technical rows inside a transaction. Never delete unique showtimes.

`canonical_key` algorithm:

```php
hash('sha256', implode('|', [
    normalized chain,
    normalized cinema,
    normalized film,
    show date Y-m-d,
    show time H:i,
    normalized screen/class or '-'
]));
```

Normalization rules:

- uppercase;
- Unicode normalize if extension is available; otherwise preserve Unicode;
- collapse whitespace;
- remove punctuation differences that do not change identity;
- remove trailing format labels from film title: `2D`, `3D`, `IMAX`, `REGULAR`, `PREMIUM`;
- map `CINÉPOLIS` and `CINEPOLIS` to `CINEPOLIS`;
- map `CINEMA 21`, `CINEMA XXI`, `21 CINEPLEX`, `M-TIX` to `XXI` only when source merchant indicates XXI;
- do not fuzzy-merge different cinemas based only on similarity; add explicit alias mapping later when verified.

### 3.3 `seatmap_snapshots` additions

```php
$table->string('method', 40)->default('unavailable_only')->after('showtime_uuid');
$table->string('confidence', 20)->default('low')->after('method');
$table->boolean('is_comparable')->default(true)->after('confidence');
$table->string('availability_event', 40)->nullable()->after('delta_occupied');
$table->text('collection_warning')->nullable()->after('availability_event');
```

Allowed `method` values:

```text
detailed_status
available_minus_total
unavailable_only
```

Allowed `confidence` values:

```text
high
medium
low
unavailable
```

Allowed `availability_event` values:

```text
initial
uptake_increase
availability_increase
unchanged
layout_changed
not_comparable
```

Snapshot calculation:

```text
if sold status is explicitly provided:
    estimated uptake = sold + reserved/held only when UI clearly labels both components
    confidence = high for sold, medium for combined unavailable signal
else if total and available are provided:
    unavailable = total - available
    confidence = medium
else if only unavailable count is provided:
    confidence = low
else:
    confidence = unavailable; do not rank value
```

Never force `sold_seats = unavailable_seats`.

## 4. Final Model and Service Structure

### Create

```text
app/Services/Seatmap/Adapters/TixIdSeatmapAdapter.php
app/Services/Seatmap/CanonicalShowtimeKey.php
app/Services/Seatmap/SeatStatusNormalizer.php
app/Services/Seatmap/SeatmapPersistenceService.php
app/Services/Seatmap/SeatmapRankingService.php
app/Services/Seatmap/SanitizesSeatmapPayload.php
```

### Modify

```text
app/Models/SeatmapSource.php
app/Models/SeatmapShowtime.php
app/Models/SeatmapSnapshot.php
app/Services/Seatmap/SeatmapAdapterRegistry.php
app/Services/Seatmap/SeatmapSourceAdapter.php
```

### Exact adapter contract

Replace the current generic array-only ambiguity with this contract while preserving compatibility:

```php
interface SeatmapSourceAdapter
{
    public function provider(): string;
    public function supportsReadOnlySeatmap(): bool;
    public function safetyNotes(): array;
    public function discoverShowtimes(array $filters = []): array;
    public function probeSeatmap(array $showtime): array;
    public function fetchSeatmapSnapshot(array $showtime): array;
}
```

`TixIdSeatmapAdapter` responsibilities:

- invoke Node workers using `Symfony\Component\Process\Process`;
- pass only non-secret filters: date, city, movie, page limit, showtime ID;
- parse worker JSON;
- reject malformed envelopes;
- reject payloads containing secret-key names;
- return normalized values;
- never persist data itself.

`SeatmapPersistenceService` owns all database writes and transactions.

`CanonicalShowtimeKey` owns film/cinema/chain normalization and key generation. Controller and command must not duplicate these rules.

`SeatStatusNormalizer` returns:

```php
[
    'total_seats' => ?int,
    'available_seats' => ?int,
    'unavailable_seats' => ?int,
    'sold_seats' => ?int,
    'reserved_seats' => ?int,
    'blocked_seats' => ?int,
    'unknown_seats' => ?int,
    'method' => string,
    'confidence' => string,
    'estimated_occupancy_percent' => ?float,
]
```

## 5. Browser Workers — Exact Contracts

### 5.1 Create `scripts/seatmap/tix-discovery.js`

Invocation:

```bash
node scripts/seatmap/tix-discovery.js \
  --date=2026-09-16 \
  --city-id=967969975509716992 \
  --max-movies=10 \
  --max-pages=3
```

Browser settings:

```js
chromium.launchPersistentContext(profilePath, {
  executablePath: process.env.SEATMAP_CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: true,
  locale: 'id-ID',
  timezoneId: 'Asia/Jakarta',
  viewport: { width: 1440, height: 1000 }
});
```

Use a unique temporary profile per process under `/tmp/sinemaku-tix-*`; delete it in `finally`. Do not reuse a long-lived authenticated profile.

Capture `response` events only when URL matches approved schedule/movie patterns. Parse JSON in memory and immediately sanitize recursively.

Forbidden output keys, case-insensitive:

```text
authorization
access_token
refresh_token
token
cookie
set-cookie
password
auth_code
client_id
phone
msisdn
otp
```

Exact output envelope:

```json
{
  "schema_version": 1,
  "provider": "tix_id",
  "status": "ok|partial|blocked|rate_limited|error",
  "captured_at": "2026-09-16T13:00:00+07:00",
  "filters": {
    "date": "2026-09-16",
    "city_id": "967969975509716992"
  },
  "coverage": {
    "movies": 10,
    "chains": ["XXI", "CGV", "CINEPOLIS"],
    "cinemas": 84,
    "showtimes": 512
  },
  "showtimes": [
    {
      "external_movie_id": "2085652682806218752",
      "external_schedule_id": "2085652683783491584",
      "external_showtime_id": "...",
      "external_cinema_id": "...",
      "film_name": "RESIDENT EVIL",
      "chain": "XXI",
      "cinema_name": "...",
      "city": "Jakarta",
      "screen_name": "REGULAR 2D",
      "show_date": "2026-09-16",
      "show_time": "19:30",
      "timezone": "Asia/Jakarta",
      "ticket_price": 50000,
      "booking_url": "https://app.tix.id/...",
      "snapshot_eligible": false
    }
  ],
  "warnings": []
}
```

No warning may contain raw response bodies or headers.

### 5.2 Create `scripts/seatmap/tix-layout-probe.js`

Invocation:

```bash
node scripts/seatmap/tix-layout-probe.js \
  --showtime-id=<id> \
  --chain=XXI \
  --timezone=Asia/Jakarta \
  --booking-url=<public-url>
```

The probe:

1. opens the public movie/showtime page;
2. does not click a seat or purchase button;
3. listens for the chain-specific `/layout` response;
4. records all request URL paths during the probe;
5. aborts immediately if a forbidden/mutating path appears;
6. returns only counts/schema shape, not secret-bearing raw headers;
7. closes browser context.

Exact result envelope:

```json
{
  "schema_version": 1,
  "provider": "tix_id",
  "showtime_id": "...",
  "chain": "XXI",
  "safety_result": "safe_read_only|requires_auth|blocked|unsafe_transactional|layout_unavailable|error",
  "layout_requested": true,
  "mutating_request_detected": false,
  "login_detected": false,
  "captcha_detected": false,
  "seat_schema": {
    "has_seat_labels": true,
    "has_distinct_statuses": true,
    "observed_statuses": ["AVAILABLE", "UNAVAILABLE"]
  },
  "warning": null
}
```

The probe is not allowed to write snapshots.

### 5.3 Create `scripts/seatmap/tix-snapshot.js`

Only create/enable after probe returns `safe_read_only` and manual code review confirms no mutating request.

Output:

```json
{
  "schema_version": 1,
  "provider": "tix_id",
  "showtime_id": "...",
  "chain": "CINEPOLIS",
  "captured_at": "ISO-8601",
  "safety_result": "safe_read_only",
  "seat_counts": {
    "total": 180,
    "available": 74,
    "unavailable": 106,
    "sold": null,
    "reserved": null,
    "blocked": null,
    "unknown": 0
  },
  "status_dictionary": {
    "available": ["AVAILABLE"],
    "unavailable": ["UNAVAILABLE"]
  },
  "layout_fingerprint": "sha256",
  "sanitized_payload": {}
}
```

## 6. Artisan Commands — Final Names and Behavior

### Create `app/Console/Commands/DiscoverTixShowtimesCommand.php`

Signature:

```php
seatmap:tix-discover
{--date= : Y-m-d, default today Asia/Jakarta}
{--city-id= : Optional TIX city ID}
{--max-movies=10}
{--max-pages=3}
{--dry-run}
```

Behavior:

- resolve `TixIdSeatmapAdapter` through registry;
- run worker;
- update TIX source health;
- normalize canonical keys;
- upsert showtimes;
- print a summary only—never raw JSON containing secrets;
- dry-run prints max five sanitized normalized rows;
- return non-zero on blocked/error, zero on ok/partial.

### Create `app/Console/Commands/ProbeTixLayoutCommand.php`

Signature:

```php
seatmap:tix-probe
{showtime : SeatmapShowtime UUID}
{--dry-run}
```

Behavior:

- requires source provider `tix_id`;
- refuses stale/past showtime;
- invokes the layout probe;
- stores only safety status and sanitized warning in source/showtime metadata;
- sets `snapshot_eligible=true` only for `safe_read_only`;
- never creates a snapshot.

### Create `app/Console/Commands/CollectTixSnapshotsCommand.php`

Signature:

```php
seatmap:tix-snapshots
{--limit=10}
{--window-before=180}
{--window-after=15}
{--dry-run}
```

Eligibility query:

- `provider=tix_id`;
- `snapshot_eligible=true`;
- active;
- show date today;
- showtime between now minus 15 minutes and now plus 180 minutes;
- source not in backoff;
- oldest `last snapshot` first.

Writes one snapshot per successful showtime. A duplicate `raw_hash` may still be stored only if at least 10 minutes elapsed, because unchanged observations are meaningful for timeline freshness. Never create more than one snapshot for the same showtime/captured minute.

### Modify existing command

`app/Console/Commands/CollectSeatmapShowtimesCommand.php` remains the direct-provider fallback. Change default provider to explicit `cinepolis`; do not pretend it supports TIX ID.

## 7. Scheduler — Exact Configuration

Modify `app/Console/Kernel.php` to:

```php
$schedule->command('seatmap:tix-discover --max-movies=40 --max-pages=5')
    ->everyThirtyMinutes()
    ->withoutOverlapping(25)
    ->runInBackground();

$schedule->command('seatmap:tix-snapshots --limit=10 --window-before=180 --window-after=15')
    ->everyTenMinutes()
    ->withoutOverlapping(9)
    ->runInBackground();

$schedule->command('seatmap:collect-showtimes --provider=cinepolis --max-movies=10')
    ->hourly()
    ->withoutOverlapping(50)
    ->runInBackground();
```

Snapshot scheduler must remain disabled/commented until at least one controlled probe is approved as `safe_read_only` in development. Add an env flag:

```env
SEATMAP_TIX_SNAPSHOTS_ENABLED=false
```

Use `config/seatmap.php`; do not read `env()` outside config.

## 8. Routes and Controller Methods — Final Contract

Modify `routes/web.php` under existing authenticated `backoffice` group:

```php
Route::get('audience-estimate', ...)->name('seatmap-monitor.index');
Route::get('audience-estimate/ranking', ...)->name('seatmap-monitor.ranking');
Route::get('audience-estimate/coverage', ...)->name('seatmap-monitor.coverage');
Route::get('audience-estimate/films/{filmKey}', ...)->name('seatmap-monitor.films.show');
Route::get('audience-estimate/films/{filmKey}/timeline', ...)->name('seatmap-monitor.films.timeline');
Route::get('audience-estimate/films/{filmKey}/cinemas', ...)->name('seatmap-monitor.films.cinemas');
Route::get('audience-estimate/sources', ...)->name('seatmap-monitor.sources');
Route::post('audience-estimate/sources/{source}/retry-discovery', ...)->name('seatmap-monitor.sources.retry');
Route::post('audience-estimate/showtimes/{showtime}/probe', ...)->name('seatmap-monitor.showtimes.probe');
```

Keep existing source/account routes only for manually authorized providers. TIX ID public source is system-managed and cannot be deleted or assigned a raw token through UI.

Split current large controller:

```text
app/Http/Controllers/SeatmapMonitorController.php
app/Http/Controllers/SeatmapSourceController.php
```

`SeatmapMonitorController` methods:

```text
index()
ranking(Request)
coverage(Request)
film($filmKey, Request)
timeline($filmKey, Request)
cinemas($filmKey, Request)
```

`SeatmapSourceController` methods:

```text
index()
store(Request)
storeAccount(Request)
deleteAccount($account)
retryDiscovery($source)
probe($showtime)
```

All retry/probe responses are JSON and UI feedback uses Swal.

## 9. Ranking API — Exact Response

`GET /backoffice/audience-estimate/ranking?date=2026-09-16&city=&chain=`

```json
{
  "date": "2026-09-16",
  "filters": {
    "city": null,
    "chain": null,
    "source": null
  },
  "coverage": {
    "tracked_sources": 2,
    "healthy_sources": 1,
    "active_chains": 3,
    "chain_labels": ["XXI", "CGV", "CINEPOLIS"],
    "cinemas": 84,
    "showtimes": 759,
    "snapshot_eligible_showtimes": 0,
    "snapshots": 0
  },
  "collection": {
    "state": "no_snapshots",
    "last_discovery_at": "ISO-8601 or null",
    "last_snapshot_at": null,
    "stale_after_minutes": 20
  },
  "data": [
    {
      "rank": 1,
      "film_key": "sha256",
      "film_name": "RESIDENT EVIL",
      "estimated_seat_uptake": null,
      "previous_estimated_seat_uptake": null,
      "delta_seat_uptake": null,
      "change_percent": null,
      "estimated_occupancy_percent": null,
      "showtime_count": 302,
      "cinema_count": 40,
      "chain_count": 3,
      "chains": ["XXI", "CGV", "CINEPOLIS"],
      "source_count": 2,
      "confidence": "unavailable",
      "method": null,
      "last_captured_at": null
    }
  ],
  "meta": {
    "disclaimer": "Public seat-map signal; not official admissions.",
    "timezone": "Asia/Jakarta"
  }
}
```

Important behavior:

- With showtimes but no snapshots, return film rows with `estimated_seat_uptake=null`, not zero.
- Rank only films with comparable snapshots. Films without snapshots appear after ranked rows under `Awaiting seat-map signal`, or in a separate `unranked` array.
- Sort by estimated uptake descending, then cinema coverage descending.
- Do not calculate the current fake momentum score from occupancy/delta. Remove it.

## 10. Page Design — Exact Metronic Layout

Reuse `layouts.page` and the project’s existing Metronic cards, badges, form controls, tables, dropdowns, and modal structure. Remove the current one-off gradient hero and custom pseudo-marketing look from `resources/views/seatmap-monitor/index.blade.php`. This is an operational admin page, not a landing page.

### 10.1 National Ranking Page

File:

```text
resources/views/seatmap-monitor/index.blade.php
```

Render this exact order:

#### A. Subheader

Left:

```text
Audience Estimate
National combined public seat-map signal
```

Right:

```text
Last discovery: <time>
Last seat snapshot: <time or No snapshots>
[Collection status badge]
```

#### B. Filter card

Single Metronic card row:

```text
Tanggal | Kota | Chain | [Audit filters dropdown: source] | Apply | Reset
```

Defaults:

- date: today Asia/Jakarta;
- city: all;
- chain: all;
- source: all and hidden inside audit filters.

Changing filters must not trigger until `Apply` is clicked. Reset restores defaults and reloads.

#### C. Five KPI cards

```text
Tracked Sources
Active Chains
Cinemas Covered
Showtimes Discovered
Seat-map Snapshots
```

Each card has:

- icon;
- real count or `—`;
- one-line status text;
- no fabricated percentage trend.

#### D. Collection readiness card

Two-column card:

Left:

```text
TIX ID Primary Aggregator
Discovery status
Observed chains
Last success
Snapshot eligibility
```

Right:

```text
Methodology
Estimated Seat Uptake = observed unavailable signal
Held/blocked seats may be included
Official admissions remain distributor-report data
```

#### E. Ranking card

Header:

```text
National Film Ranking
[Freshness badge] [Export disabled until real snapshots]
```

Table columns:

```text
#
Film
Estimated Seat Uptake
Δ Signal
Estimated Occupancy
Showtimes
Cinemas
Coverage
Confidence
Updated
Action
```

Remove:

```text
Daily Est. Adm.
Score
Momentum Flash
```

Row styling:

- rank: compact circular number, no oversized typography;
- film: title + chain labels + capture freshness;
- confidence: badge with tooltip;
- action: `View detail` icon/button;
- unavailable metrics: `—`, never `0`.

#### F. Exact page states

`loading`:

- table skeleton rows;
- filter disabled during request;
- no blocking Swal for normal loading.

`showtimes_without_snapshots`:

```text
Jadwal publik sudah dikumpulkan
759 showtimes dari 3 film telah ditemukan. Ranking belum dihitung karena seat-layout read-only belum terverifikasi atau belum memiliki snapshot.
[View source health]
```

`no_showtimes`:

```text
Belum ada jadwal publik
Periksa status source atau jalankan discovery ulang.
[View source health]
```

`blocked`:

```text
Seat-map collection diblokir oleh source
Data discovery dan snapshot lama tetap tersedia. Sistem tidak mencoba bypass.
[View details]
```

`filter_empty`:

```text
Tidak ada data untuk kombinasi filter ini
Reset filter atau pilih tanggal lain.
```

`error`:

- retain current rendered data;
- show Swal error;
- show non-destructive inline stale banner.

### 10.2 Film Detail Page

Create:

```text
resources/views/seatmap-monitor/film.blade.php
```

Layout order:

1. breadcrumb back to National Ranking;
2. title, date, chain/city audit filters;
3. KPI row:
   - Estimated Seat Uptake;
   - Estimated Occupancy;
   - Observed Showtimes;
   - Cinemas Covered;
   - Confidence;
4. primary line chart: total estimated occupied signal over captured time;
5. secondary bar chart: delta per capture interval;
6. cinema/showtime breakdown table;
7. source provenance and methodology accordion.

Cinema table columns:

```text
Chain
Cinema
Screen/Class
Showtime
Total Visible
Available
Unavailable Signal
Sold (if explicit)
Held/Reserved (if explicit)
Confidence
Captured At
```

Chart rules:

- use actual timestamps only;
- gaps remain gaps;
- positive delta: blue/green;
- availability increase: amber with tooltip `Possible hold expiry or availability resync`;
- no interpolation;
- no cumulative admission extrapolation beyond observed seats.

Use the chart library already present in the project. Do not add a new chart package unless the existing library cannot render line/bar charts.

### 10.3 Source Coverage & Collection Health Page

Replace the primary purpose of:

```text
resources/views/seatmap-monitor/sources.blade.php
```

Page order:

1. title + safety statement;
2. primary TIX ID aggregator card;
3. direct fallback cards;
4. source health table;
5. manual authorized-source form in a collapsed panel;
6. Account Vault only for manual providers that explicitly require authorized credentials.

TIX ID card fields:

```text
Method: Public browser app observation
Coverage: observed chains only
Discovery status
Layout safety status
Last discovery
Last snapshot
Backoff until
Latest sanitized warning
```

Actions:

```text
Retry discovery
Probe one eligible showtime
View recent health events
```

All actions require Swal confirmation/result. Retry is safe and read-only; it must not open seat selection.

Do not display these inputs for system-managed TIX ID:

```text
username
password
access token
raw token
cookie
```

Manual account form is hidden unless a manually added source is selected.

### 10.4 Reconciliation Section

On film detail, add a `Distributor reconciliation` card only when matching `pelaporans` data exists.

Display side by side:

```text
Public Seat-map Signal
Distributor Report Admissions
Coverage difference
Date/cinema coverage
Last updated
```

Do not calculate a variance if the compared cinema/date/showtime coverage is not equivalent. Show `Not comparable` with the reason.

## 11. Frontend JavaScript — Exact File Split

Do not keep all logic inline in Blade. Create:

```text
public/js/seatmap/audience-ranking.js
public/js/seatmap/audience-film.js
public/js/seatmap/source-health.js
```

Blade only provides route/data config:

```html
<script>
window.SeatmapConfig = @json([...]);
</script>
```

JavaScript requirements:

- use `fetch` with `Accept: application/json` and `X-Requested-With`;
- central `requestJson()` helper per file or shared existing helper;
- abort previous fetch when filters change/reload;
- escape all user/source-provided text before HTML insertion;
- disable action buttons during request;
- use Swal for errors, confirmations, and successful manual actions;
- do not use native alert/confirm/prompt;
- do not log response payloads to console in production;
- preserve table data if refresh fails.

## 12. Exact Implementation Order and Tests

### Task 1 — Add schema tests and migration

Create:

```text
tests/Feature/SeatmapAggregationSchemaTest.php
```

Assertions:

- all new source/showtime/snapshot columns exist;
- canonical unique/index definitions work;
- unrelated pending vendor migration is not applied by the targeted test setup.

Run targeted migration only:

```bash
php artisan migrate --path=database/migrations/2026_09_16_140000_add_tix_aggregation_fields_to_seatmap_tables.php --force
```

### Task 2 — Canonical key and status normalizer

Create:

```text
tests/Unit/CanonicalShowtimeKeyTest.php
tests/Unit/SeatStatusNormalizerTest.php
```

Required cases:

- Cinepolis accents/case normalize consistently;
- film suffix differences merge;
- same film/cinema/time across TIX and direct source yields same canonical key;
- different screen or time yields different key;
- explicit sold remains sold;
- unavailable is not copied into sold;
- total minus available computes unavailable;
- invalid negative counts return non-comparable.

### Task 3 — Sanitizer

Create:

```text
tests/Unit/SeatmapPayloadSanitizerTest.php
```

Test recursive removal of token, cookie, authorization, phone, OTP, and credential-like fields at any nesting level. Test that warning/log output never includes provided secret fixture values.

### Task 4 — TIX discovery worker fixture parser

Create sanitized fixtures:

```text
tests/Fixtures/seatmap/tix/movie.json
tests/Fixtures/seatmap/tix/schedules-xxi.json
tests/Fixtures/seatmap/tix/schedules-cgv.json
tests/Fixtures/seatmap/tix/schedules-cinepolis.json
```

Create:

```text
tests/Unit/TixIdWorkerContractTest.php
```

Validate exact JSON envelope and merchant mapping. Fixtures must contain no real token/cookie/user data.

### Task 5 — Live TIX discovery worker

Implement `tix-discovery.js`, run syntax check, then one-movie/one-city dry-run. Capture only sanitized output. Verify actual records, not merchant constants alone, before claiming a chain is covered.

### Task 6 — Persistence/idempotency

Create:

```text
tests/Feature/TixIdCollectionCommandTest.php
```

Cases:

- first run creates source/showtimes;
- repeated same fixture keeps row count stable;
- direct Cinepolis + TIX matching showtime share canonical key;
- source IDs stay independent;
- stale records are not immediately deleted;
- failures update health without zeroing old data.

### Task 7 — Layout safety probe

Create fixture-based tests first, then the worker. Cases:

- read-only layout response -> safe;
- login route -> requires authentication;
- CAPTCHA marker -> blocked;
- order/payment/reserve request -> unsafe transactional;
- no layout response -> layout unavailable.

Run only one live showtime probe. Save no snapshot during this task.

### Task 8 — Snapshot worker and persistence

Proceed only if Task 7 live result is `safe_read_only` and request log contains no mutating endpoint.

Tests:

- one successful snapshot;
- unchanged snapshot freshness;
- uptake increase;
- availability increase/hold expiry label;
- layout capacity change -> not comparable;
- sanitized raw payload;
- one snapshot per captured minute.

### Task 9 — Ranking service/API

Create:

```text
tests/Feature/SeatmapRankingApiTest.php
```

Cases:

- showtimes/no snapshots returns null estimates and no fake rank;
- combined TIX/direct canonical showtime counted once;
- source/chain/cinema counts correct;
- confidence/method propagated;
- previous comparable snapshot used for delta;
- negative delta labelled availability increase;
- date/city/chain filters;
- no-result state distinct from source failure.

### Task 10 — National ranking UI

Rewrite Blade to exact layout and create `audience-ranking.js`.

Browser QA at desktop viewport:

- 1440×900 primary;
- 1280×800 minimum supported admin desktop;
- verify no horizontal overflow outside responsive table;
- verify loading/no snapshots/blocked/filter-empty/error states;
- verify Swal behavior;
- verify source filter is under Audit filters.

### Task 11 — Film detail UI

Create route/controller/view/JS. Verify charts with real snapshots only. Test no-snapshot state and availability-increase tooltip.

### Task 12 — Source health UI

Rewrite sources page. Verify TIX card has no credential fields. Verify manual vault remains available only for selected manual sources. Verify retry/probe actions and sanitized errors.

### Task 13 — Reconciliation

Use existing Cinepolis PDF/import data. Match only normalized film/date/cinema coverage. Display `Not comparable` when scope differs. Do not mutate official report records.

### Task 14 — Scheduler and operations

Add `config/seatmap.php`, env flag, scheduler entries, source backoff, and health status. Snapshot scheduler remains disabled until approved safe probe.

### Task 15 — Final regression and live verification

Run:

```bash
php artisan migrate:status --no-interaction
php artisan schedule:list
php artisan route:list | grep audience-estimate
php artisan test
php artisan view:cache
node --check scripts/seatmap/tix-discovery.js
node --check scripts/seatmap/tix-layout-probe.js
node --check scripts/seatmap/tix-snapshot.js
php artisan seatmap:tix-discover --max-movies=1 --max-pages=1 --dry-run
php artisan seatmap:tix-probe <showtime-uuid> --dry-run
php artisan seatmap:tix-snapshots --limit=1 --dry-run  # only if probe approved
git diff --check
```

Then browser verify:

```text
/backoffice/audience-estimate
/backoffice/audience-estimate/films/{filmKey}
/backoffice/audience-estimate/sources
```

## 13. Definition of Done

The feature is done only when all are true:

- TIX ID discovery writes real showtimes to the database;
- actual observed records prove coverage for each claimed chain;
- TIX/direct duplicates share canonical identity and rank once;
- no secrets are present in output, logs, database payloads, fixtures, or Git diff;
- layout probe has a recorded safety result;
- snapshot polling is enabled only if read-only safety is proven;
- national ranking shows null/empty readiness rather than fake zero values;
- film detail explains every estimate through real snapshots;
- source page distinguishes discovery from snapshot eligibility;
- all operational UI feedback uses Swal;
- UI uses existing Metronic structure and components;
- distributor reports remain separate, official comparator data;
- full test suite, syntax checks, view cache, routes, migration status, scheduler, and browser QA pass.

## 14. Stop Conditions

Stop snapshot implementation and keep discovery-only mode if any of these occur:

- layout requires selecting a seat;
- layout request creates order/hold/reservation state;
- login/OTP/CAPTCHA/device attestation is mandatory;
- token must be extracted, hard-coded, replayed, or persisted outside official browser context;
- source repeatedly returns 403/429 despite crawl delay/backoff;
- seat states cannot be interpreted without unsafe transaction steps.

In discovery-only mode, the UI must still show real film/cinema/showtime coverage and explicitly state that seat uptake is unavailable.

## 15. Source References

- https://tix.id/faq-microsite.html
- https://app.tix.id/robots.txt
- https://app.tix.id/movies/resident-evil-2085652682806218752/2026-09-16
- https://app.tix.id/main.dart.js
