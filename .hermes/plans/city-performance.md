# City Performance — Showtime-Based Film Distribution Estimates

## Goal
Build a separate `/backoffice/city-performance` module that compares a film's observed public showtime distribution by city and, only when coverage is sufficient, allocates Cinepoint national daily admissions into transparent city-level ranges.

## Background
Cinepoint provides national daily admissions and total admissions, but not a verified public city breakdown. Seat-map collection through a consumer TIX ID account is excluded because TIX ID terms prohibit automated copying/scraping and commercial reuse without written permission. The module therefore starts with public, non-transactional showtime discovery and labels all derived audience figures as estimates.

## Scope in
- Separate backoffice route/menu: `/backoffice/city-performance`.
- Feasibility probe for a small city set before nationwide collection.
- Source adapter boundary so each public showtime provider has an explicit parser, capability declaration, rate limit, and coverage report.
- Immutable collection runs, city/cinema/showtime observations, film mappings, and derived estimates.
- Exact title/source-ID mappings plus an admin review queue for ambiguous mappings.
- Weighted showtime distribution using documented time/format/capacity factors.
- City share, relative performance index, estimated admissions range, confidence, and coverage warnings.
- Integration with complete Cinepoint daily snapshots without changing Cinepoint tables.
- VPS collector → signed Laravel ingest using the existing remote-worker boundary.
- Backoffice filters for date, film, city, network, confidence, and collection status.
- Persistent source attribution, method version, coverage, and last successful sync.

## Scope out
- TIX ID login, password, OTP, cookies, private tokens, order flow, cart, reservation, payment, seat selection, or seat-map automation.
- Claims of official city admissions.
- Fabricated studio capacities or dummy rankings.
- Nationwide rollout before the feasibility probe proves usable coverage.
- Mixing city estimates with Cinepoint figures as if they were the same metric.
- Automatic fuzzy mapping when multiple films are plausible.

## Phase 0 — Source feasibility probe
Target cities: Jakarta, Bandung, Surabaya, Medan, and Makassar.

For one date and a small sample of currently showing films:
- Identify a permitted public non-transactional showtime source.
- Capture city, cinema, chain, film identity, date, time, studio/format, and price only when visibly published.
- Record source-reported pagination/coverage and failed cities.
- Verify stable IDs, deduplication, rate behavior, and title mapping to Cinepoint.
- Stop if the source requires login, OTP, booking flow, a private token, or prohibited automated access.

Acceptance gate: at least 70% of target cities, 85% valid showtime rows, stable identity for most rows, and no transaction/login dependency. Otherwise report the blocker and do not publish admissions estimates.

## Phase 1 — Data foundation
Likely files:
- `database/migrations/*create_city_performance_tables.php`
- `app/Services/CityPerformance/*`
- `app/Http/Controllers/CityPerformanceController.php`
- `routes/web.php`
- `resources/views/seatmap-monitor/city-performance.blade.php`
- `tests/Feature/CityPerformance*Test.php`
- `tests/Unit/CityPerformance*Test.php`

Tables:
- `city_performance_sources`
- `city_performance_cities`
- `city_performance_cinemas`
- `city_performance_movie_mappings`
- `city_performance_collection_runs`
- `city_performance_city_runs`
- `city_performance_showtimes`
- `city_performance_estimates`

Every run stores source, period, method version, coverage, status, error, and timestamps. Showtime rows are immutable observations with a deterministic identity hash and unique run/hash constraint.

## Phase 2 — Derivation
For film `f` and city `c`:
- `weighted_showtime = sum(showtime_weight)`.
- `city_share = weighted_showtime_city / weighted_showtime_film_all_cities`.
- `estimated_admissions_mid = cinepoint_daily_admissions_f * city_share`.
- Use largest-remainder rounding when a displayed allocation must sum to the national figure.
- Publish lower/mid/upper range based on coverage/confidence, not false precision.
- `LPI = city film showtime share / national film showtime share * 100`.
- Labels: very strong, strong, normal, low, very low.

Initial weights are versioned and conservative: time-of-day and explicitly observed format factors only; no invented capacity. Add capacity only after verified source/master data exists.

## Phase 3 — Backoffice UX
- Summary: source, date, coverage, last sync, Cinepoint period, confidence.
- Filters: date, film, city, chain, confidence.
- Table: film, city, showtimes, share, estimated range, LPI, confidence.
- Detail: city distribution, chain/showtime breakdown, failed cities, mapping warnings, methodology.
- Empty/partial/failed states must explain why estimates are unavailable and retain the prior successful run.
- All operational feedback uses SweetAlert2; no native alerts.

## Collection and deployment
- VPS owns browser/public-source collection; Hostinger owns Laravel/database.
- Use the existing signed HTTPS ingest boundary and job lease model.
- Do not expose the database or SSH trigger.
- Separate feasibility/manual sync from scheduled collection until one complete run is read back from the database and UI.
- Suggested refresh after acceptance: 05:00, 11:00, 17:00, and 23:00 WIB; confirm after probe.

## Acceptance criteria
- Public source and permission boundary documented.
- Feasibility run reports exact requested/collected/failed city and showtime counts.
- No login, OTP, transaction, private token, or secret is stored.
- Partial source runs never replace a complete prior run.
- Stable film/cinema/showtime IDs are deduplicated.
- Estimates reconcile to Cinepoint national admissions when published.
- Ambiguous mappings remain reviewable and do not auto-publish.
- City Performance is separate from Cinepoint navigation and tables.
- Local tests, Blade cache, route list, Node syntax, and a rendered browser smoke test pass.
- Production scheduling remains disabled until end-to-end delivery is verified.

## Verification
- TDD unit tests for normalization, dedupe, coverage gate, weighting, largest remainder, LPI, confidence, and mapping ambiguity.
- Feature tests for authenticated route, run status, ingest validation, prior-run retention, and filters.
- Run the source probe serially with low rate and save redacted fixture metadata.
- Verify exact database counts and dashboard output after a complete local fixture run.
- Test partial/failed runs and confirm the previous successful run remains visible.

## Risks / open questions
- A public source with reliable city-level showtime coverage may not exist; the probe must be allowed to fail honestly.
- Showtime allocation is a proxy, not admissions measurement; external distributor reports can calibrate but not prove each city's count.
- Cinema capacity is often unavailable or inconsistent; do not invent it.
- Commercial redistribution of third-party data may require written permission.
- Method weights need versioned calibration after real observations.
