# Ringkasan Pengembangan Fitur Finance & Reporting

Dokumen ini merangkum task yang sudah dibuat untuk meningkatkan modul laporan agar lebih informatif, profesional, dan mudah dipakai oleh user finance maupun management.

## 1. Penambahan Kolom Royalty dan Total di Export Detail

Pada export detail laporan, ditambahkan kolom:

- `Royalty (1.5%)`
- `Total Akhir`

Tujuannya agar file Excel detail tidak hanya menampilkan data penonton dan gross, tetapi juga sudah membawa perhitungan pembagian pendapatan sampai estimasi nilai akhir untuk PH.

Rumus utama:

```text
Royalty = Share PH x 1.5%
Total Akhir = Net - Share PH - Royalty
```

## 2. Perhitungan Pajak Summary Menggunakan Master Bioskop

Perhitungan pajak pada summary tidak lagi bergantung penuh pada nilai `net` yang tersimpan di data pelaporan, karena ada kemungkinan nilai `net` belum terupdate.

Sekarang pajak dihitung ulang dari:

```text
Pajak = Gross x Pajak Bioskop
Net = Gross - Pajak
```

Nilai pajak diambil dari master bioskop, sehingga hasil summary lebih konsisten dengan data master.

## 3. Penambahan Row Total di Export Detail

Pada export detail Excel ditambahkan row total di bagian paling bawah.

Row ini menghitung total dari kolom-kolom penting seperti:

- jumlah penonton per show
- total penonton
- gross
- pajak
- net
- share PH
- royalty
- total akhir

Tujuannya agar user tidak perlu menghitung manual setelah export.

## 4. Redesign PDF Export Laporan Omset

Tampilan export PDF pada halaman Rekap Omset dibuat lebih profesional.

Perubahan utama:

- layout lebih rapi seperti accounting report
- header laporan lebih jelas
- logo Sinemaku ditampilkan
- filter laporan ditampilkan dalam format yang lebih bersih
- tabel dibuat lebih formal
- footer total dibuat lebih mudah dibaca

Catatan khusus: warna row total juga diperbaiki agar teks tetap kontras dan mudah dibaca.

## 5. Redesign PDF Dashboard

Export PDF pada dashboard juga dibuat lebih profesional dan informatif.

Perubahan utama:

- layout laporan lebih formal
- visual summary dibuat lebih bersih
- chart dan tabel disusun agar lebih mudah dibaca
- bug pada tabel samping chart Audience Performance juga diperbaiki

Tujuannya agar PDF dashboard layak digunakan sebagai laporan internal atau bahan presentasi.

## 6. Revenue Waterfall

Ditambahkan panel `Revenue Waterfall` pada Rekap Omset.

Fitur ini menjelaskan alur pendapatan dari gross sampai estimasi pendapatan PH.

Alur:

```text
Gross Box Office
- Pajak
= Net Box Office
- Share 50%
- Royalty 1.5%
= Total PH / Estimasi Pendapatan PH
```

Manfaat untuk user:

- user bisa melihat breakdown pendapatan dengan cepat
- lebih mudah memahami komponen pengurang
- membantu membaca laporan secara finance-oriented

## 7. ATP / Average Ticket Price

Ditambahkan metrik `ATP` atau `Average Ticket Price`.

Rumus:

```text
ATP = Gross / Jumlah Penonton
```

ATP ditampilkan di:

- summary omset
- revenue waterfall
- export detail
- total row export detail

Manfaat:

- mengetahui rata-rata harga tiket aktual
- membandingkan performa antar kategori, kota, atau bioskop
- membantu membaca apakah pendapatan naik karena penonton naik atau harga tiket lebih tinggi

## 8. Effective Tax Rate

Ditambahkan metrik `Effective Tax Rate`.

Rumus:

```text
Effective Tax Rate = Total Pajak / Total Gross x 100
```

Perhitungan dibuat weighted, bukan rata-rata biasa. Artinya angka total dihitung berdasarkan total pajak dan total gross, sehingga lebih akurat untuk laporan finance.

Ditampilkan di:

- summary omset
- revenue waterfall
- export detail
- total row export detail

Manfaat:

- melihat rata-rata pajak efektif dari data yang difilter
- membantu mengecek apakah pajak antar bioskop/kota sudah masuk akal
- memudahkan review data pajak

## 9. Occupancy Rate

Ditambahkan metrik `Occupancy Rate`.

Rumus:

```text
Occupancy Rate = Jumlah Penonton / Kapasitas Tersedia x 100
```

Kapasitas tersedia dihitung dari master kapasitas studio berdasarkan data show yang masuk filter.

Ditampilkan di:

- summary omset
- revenue waterfall
- export detail
- total row export detail

Manfaat:

- mengetahui tingkat keterisian studio
- membaca efisiensi show
- membantu evaluasi jadwal, kota, bioskop, atau tipe tiket

## 10. Cinema Leaderboard / Performance Ranking

Ditambahkan tab `Cinema Leaderboard` pada Rekap Omset.

Tab ini menampilkan ranking bioskop berdasarkan kontribusi `Total PH`.

Kolom utama:

- rank
- kota
- nama bioskop
- penonton
- kapasitas tersedia
- occupancy
- gross
- ATP
- net
- total PH

Manfaat:

- mengetahui bioskop paling berkontribusi
- membandingkan bioskop berdasarkan metrik finance dan operasional
- membantu management melihat top performer

## 11. Tab Omset dan Cinema Leaderboard

Tabel pada Rekap Omset tidak lagi ditumpuk dalam satu halaman.

Sekarang dibuat dalam tab:

- `Omset`
- `Cinema Leaderboard`
- `Audit Checks`

Tujuannya agar halaman lebih bersih, tidak terlalu panjang, dan user bisa fokus pada jenis analisa yang dibutuhkan.

## 12. Horizontal Scroll untuk Tabel

Responsive collapse DataTables dimatikan karena sebelumnya memunculkan tombol `+` pada tabel.

Sekarang tabel menggunakan horizontal scroll.

Manfaat:

- semua kolom tetap terlihat sebagai tabel normal
- user cukup scroll ke kanan jika kolom banyak
- tampilan lebih cocok untuk data finance/accounting

## 13. Audit Checks / Data Quality

Ditambahkan tab `Audit Checks` untuk mendeteksi data yang perlu direview sebelum laporan dipakai.

Audit yang dicek:

- kapasitas studio belum terdaftar
- kapasitas studio kosong
- pajak bioskop belum terisi
- occupancy per show lebih dari 100%
- gross tidak sesuai `harga x penonton`

Manfaat:

- membantu finance menemukan data bermasalah
- mengurangi risiko laporan final salah
- mempercepat proses validasi data

## 14. Finance Insight

Dibuat menu baru `Finance Insight`.

Menu ini adalah executive summary untuk management dan finance.

Isi halaman:

- Gross Box Office
- Total PH / Estimasi Pendapatan PH
- total penonton
- ATP
- occupancy
- effective tax rate
- jumlah bioskop dan kota
- jumlah audit issue
- top category
- top cinema
- top city
- highest occupancy
- management notes otomatis
- audit risk summary
- top 5 cinema by Total PH

Manfaat:

- user bisa membaca performa laporan tanpa harus membuka tabel panjang
- management mendapat snapshot yang cepat dan profesional
- finance bisa langsung melihat risiko data dan performer utama

## 15. Trend Analysis

Dibuat menu baru `Trend Analysis`.

Menu ini digunakan untuk membaca movement harian berdasarkan filter yang dipilih.

Isi halaman:

- grafik harian Total PH
- grafik harian Gross
- grafik harian Penonton
- KPI Total PH
- KPI Gross
- KPI Penonton
- rata-rata PH per hari
- movement dari hari pertama ke hari terakhir
- best day
- movement notes otomatis
- tabel daily movement

Kolom tabel movement:

- tanggal
- penonton
- gross
- total PH
- ATP
- occupancy
- PH growth
- gross growth
- audience growth

Manfaat:

- melihat apakah performa sedang naik atau turun
- membaca hari terbaik dalam periode
- memahami perubahan harian secara lebih profesional
- membantu analisa performa film dari waktu ke waktu

## 16. Penjelasan Istilah Total PH

`Total PH` adalah estimasi pendapatan untuk pihak PH / Production House setelah pajak, pembagian share, dan royalty.

Rumus:

```text
Gross - Pajak = Net
Share PH = Net / 2
Royalty = Share PH x 1.5%
Total PH = Share PH - Royalty
```

Contoh:

```text
Gross        = 100,000,000
Pajak 10%    = 10,000,000
Net          = 90,000,000
Share PH     = 45,000,000
Royalty 1.5% = 675,000
Total PH     = 44,325,000
```

Istilah yang lebih mudah dipahami user:

- Estimasi Pendapatan PH
- Net PH Share
- PH Net Revenue
- Total Untuk PH

Rekomendasi istilah paling jelas:

```text
Estimasi Pendapatan PH
```

## Halaman dan Menu Baru

Menu baru yang ditambahkan:

- `Finance Insight`
- `Trend Analysis`

Halaman Rekap Omset tetap dipakai untuk detail laporan dan tab analisa langsung.

Halaman baru dibuat agar fitur executive summary dan trend tidak menumpuk di halaman Rekap Omset.

## File Utama yang Banyak Berubah

- `app/Http/Controllers/LaporanController.php`
- `resources/views/laporan/index.blade.php`
- `resources/views/laporan/finance_insight.blade.php`
- `resources/views/laporan/trend_analysis.blade.php`
- `resources/views/backoffice/dashboard.blade.php`
- `resources/views/partials/menu.blade.php`
- `routes/web.php`

## Catatan untuk User

Dengan fitur baru ini, user dapat:

- membaca ringkasan performa dengan cepat
- mengecek kualitas data sebelum laporan final
- memahami pembagian pendapatan
- melihat bioskop dan kota dengan kontribusi terbesar
- membaca trend harian
- export laporan yang lebih rapi dan siap dibagikan
