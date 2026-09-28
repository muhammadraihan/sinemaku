# XXI PDF Import + Master Harga

## Goal
Menambahkan master harga tiket XXI per bioskop dan import laporan XXI PDF dengan harga regular berbasis tanggal laporan, tanpa mengubah alur Excel XXI yang sudah berjalan.

## Background
Laporan PDF XXI menyediakan tanggal, film, kota, bioskop, studio, kapasitas, show 1-7, PTN, dan FP, tetapi tidak menyediakan harga tiket maupun jam aktual. Sheet master harga Excel menyediakan tiga tarif per bioskop: Senin-Kamis, Jumat, Sabtu-Minggu & tanggal merah. Untuk sementara seluruh laporan XXI diperlakukan sebagai tipe tiket REGULAR.

## Scope in
- Tabel child harga per bioskop, tipe tiket, periode berlaku, dan tiga tarif hari.
- Master hari libur manual untuk menentukan tarif tanggal merah.
- UI Blade pada edit Master Bioskop untuk daftar/tambah/edit/hapus harga.
- Seed/import awal dari Sheet1 Excel hanya sebagai proses terpisah yang dapat dijalankan eksplisit; jangan commit fixture eksternal.
- Resolver harga tanggal laporan dengan prioritas override tanggal (reserved for future), libur/weekend, Jumat, Senin-Kamis.
- PDF XXI parser menggunakan Poppler `pdftotext -layout`, parsing city sections dan row `cinema/studio/capacity/show1-7/PTN/FP`.
- Mapping PDF cinema/studio ke Master Bioskop/Kapasitas; tipe tiket regular.
- Hardcode show 1-7: 11:00, 13:00, 15:00, 17:00, 19:00, 21:00, 23:00.
- Preview/confirm tetap user-bound, no canonical writes on preview, reconciliation PTN/FP, duplicate blocking, atomic history + report insert.
- Regression tests for price resolver, PDF parser, show 7, missing price/capacity mapping, and successful confirm.

## Scope out
- IMAX/Premiere-specific reports and pricing.
- Automatic OCR or coordinate-based PDF extraction.
- Changing existing XXI Excel parser semantics.
- Production database migration, deployment, push, or commit.
- Browser QA requiring unavailable authenticated local session.

## Assumptions
- `type_tikets` kategori XXI has or can use a `REGULAR` record; no automatic IMAX/Premiere inference.
- `Kapasitas` maps a cinema/studio to one regular ticket type for this flow.
- `pdftotext` is available on target runtime; parser must return an actionable dependency error if unavailable.
- PDF show 7 maps to 23:00.
- Existing report canonical fields can represent zero-value FP rows.

## Likely files/systems
- New migrations/models: cinema ticket prices, holidays.
- MasterBioskop model/controller/edit Blade and routes.
- New price resolver/service and tests.
- New XXI PDF parser/service, controller upload routing, preview normalization, and tests.
- Existing upload Blade and feature tests.

## Acceptance criteria
- Admin can maintain one or more REGULAR price records per cinema with Mon-Thu, Friday, weekend/holiday prices and effective period.
- Overlapping price periods for cinema + ticket type are rejected.
- Resolver selects the correct price for weekday, Friday, weekend, and configured holiday.
- PDF parser returns all source rows including show 7 and reconciles printed PTN/FP totals.
- Show 7 is persisted as 23:00, not dropped.
- Missing cinema/studio/price mappings block confirm with actionable preview issues.
- Preview writes zero canonical `pelaporans`; confirm writes once and creates upload history atomically.
- Existing XXI Excel and full regression suites remain green.
- No commit/push is performed.

## Verification
- Focused unit tests for price resolver and PDF parser.
- Feature tests for PDF preview/confirm, mapping blocks, show 7, duplicate/token lifecycle.
- `php artisan view:cache`.
- PHP lint for changed PHP files.
- `git diff --check`.
- Full `php artisan test`.
- Inspect git status/diff and confirm no external fixture is staged.

## Risks
- PDF parser dependency/runtime differs between local and production; surface missing Poppler clearly.
- Existing capacity rows may have non-regular or duplicate studio mappings; preview must block ambiguity rather than guess.
- Current database may be unavailable for migration verification; tests must cover schema contract independently.
- PDF import controller has a large legacy flow; changes must remain provider-isolated.
