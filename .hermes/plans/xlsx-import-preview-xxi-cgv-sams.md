# Preview Import Excel: XXI, CGV, dan SAMS

## Goal
Mengubah import Excel XXI, CGV, dan SAMS menjadi alur preview yang setara Cinepolis: validasi dan mapping tampil sebelum ada data laporan yang disimpan.

## Background
Importer lama menyimpan baris valid langsung dan menghasilkan file Excel error untuk baris gagal. Operator harus berpindah ke halaman master untuk memperbaiki data.

## Scope
### In
- Parse dan mapping validation tanpa write ke `pelaporans`.
- Preview satu modal bersama untuk XXI, CGV, dan SAMS.
- Ringkasan dan tabel detail per baris dengan status mapping dan alasan blokir.
- Tombol quick-master untuk Bioskop, Film, Tipe Tiket, dan Kapasitas bila error terkait muncul.
- Confirm memakai token cache user-bound; server memvalidasi ulang dan insert transaksional.
- Duplicate, token kedaluwarsa, token milik user lain, dan mapping belum lengkap diblokir.

### Out
- Mengubah format/template Excel distributor.
- Mengubah alur Cinepolis yang sudah ada, selain ekstraksi komponen preview bersama bila aman.
- Mengubah file lokal unrelated milik user.

## Assumptions
- Struktur kolom dan normalisasi tiap importer harus diambil dari controller lama, bukan disamakan secara asumtif.
- Data master yang ditambah dari preview wajib berasal dari nilai/row pada token preview.

## Files / Systems Likely to Change
- `app/Http/Controllers/PelaporanController.php`
- `routes/web.php`
- `resources/views/pelaporan/index.blade.php`
- Feature tests dan fixture Excel per profile bila fixture tersedia/dibuat dari format yang ada.

## Acceptance Criteria
- Upload XXI/CGV/SAMS tidak membuat `pelaporans` sebelum confirm.
- Semua error mapping tampil di preview sebagai `<ul>` yang jelas, tanpa download error spreadsheet.
- Operator dapat memperbaiki master yang hilang dari popup lalu preview diperiksa ulang tanpa upload ulang.
- Confirm memasukkan tepat baris preview yang valid dan token dikonsumsi setelah sukses.
- Tidak ada importer yang mengimpor parsial saat masih ada blocking issue.

## Verification
- Parser/feature test per provider: preview empty write, blocked mapping, quick master, confirm, duplicate/token boundaries.
- Full Laravel suite, Blade cache, route list, PHP syntax, `git diff --check`.
- Browser test upload → preview → quick master → refreshed preview → confirm bila local server/authenticated session tersedia.

## Risks / Open Questions
- Parser lama mungkin langsung mengimpor sambil membaca spreadsheet, sehingga perlu diekstrak menjadi profile normalizer bertahap.
- Format Excel nyata untuk tiga provider perlu fixture yang mewakili baris normal dan error mapping agar parser kontrak tidak mengandalkan asumsi.
