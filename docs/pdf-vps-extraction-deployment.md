# Panduan lengkap: ekstraksi PDF XXI di Hostinger

> **Kabar baik: VPS kemungkinan besar tidak diperlukan.**
>
> Sejak versi ini, PDF XXI dibaca oleh **ekstraktor bawaan (murni PHP)** yang tidak
> membutuhkan `pdftotext`, Node, maupun layanan luar. Sudah diuji dengan berkas
> `16MPEA_20260926.pdf`: **534 baris, PTN 16.274, FP 47, tanggal 2026-09-26** —
> identik dengan hasil `pdftotext`.
>
> **Langkah yang perlu Anda lakukan di Hostinger sekarang hanya dua:**
>
> ```sh
> php artisan config:cache
> php artisan xxi:pdf-doctor
> ```
>
> Perintah tanpa `--pdf` sudah cukup untuk memastikan import siap: ia akan
> menampilkan `Jalur 2 (ekstraktor bawaan PHP): AKTIF`. Bila ingin menguji dengan
> berkas nyata, lihat bagian **Soal `--pdf`** di bawah.
>
> **Soal `--pdf`: Anda tidak perlu tahu path absolut.** Cara termudah — taruh PDF
> di `storage/app`, lalu sebutkan namanya saja:
>
> ```sh
> php artisan xxi:pdf-doctor --pdf=laporan-xxi.pdf
> ```
>
> Bingung menaruh di mana? Jalankan perintah **tanpa `--pdf`**; hasilnya
> mencetak sendiri folder yang bisa dipakai beserta path absolutnya, contoh:
>
> ```text
> Tempel PDF Anda di salah satu folder ini, lalu sebutkan nama berkasnya saja:
>   /home/username/domains/domain-anda/storage/app (ada)
>   /home/username/domains/domain-anda (ada)
> ```
>
> Path absolut boleh disalin langsung dari situ, misalnya
> `--pdf=/home/username/.../storage/app/laporan-xxi.pdf`. Bila berkas tidak
> ditemukan, perintah juga memberi tahu path mana saja yang sudah dicoba.
>
> Harus muncul `OK Ekstraksi berhasil: ... baris`. Bila muncul keterangan yang
> menyebut layanan VPS padahal Anda tidak memasangnya, kosongkan `PDF_EXTRACT_URL`
> di `.env` lalu ulangi `config:cache`.
>
> Dokumen di bawah ini hanya perlu diikuti bila ekstraktor bawaan **gagal**
> (misalnya PDF dari distributor memakai enkripsi AES) dan Anda memilih
> menyiapkan layanan ekstraksi di VPS sebagai cadangan.

Alurnya:

```text
[Hostinger]  aplikasi Laravel  ---HTTPS + tanda tangan-->  [VPS]  layanan ekstraksi  -->  pdftotext
```

Urutan yang dipakai aplikasi (tidak pernah gagal diam-diam):

```text
binary pdftotext lokal  ->  ekstraktor bawaan PHP  ->  layanan VPS  ->  pesan error yang jelas
```

Aplikasi tetap di Hostinger. VPS hanya mengerjakan satu hal: mengubah PDF menjadi teks.

**Ringkasan waktu:** Bagian A–B sekitar 15 menit, Bagian C sekitar 10 menit, Bagian D–E sekitar 10 menit.

**Semua yang perlu Anda siapkan di awal:**

| Kebutuhan | Contoh | Dari mana |
|---|---|---|
| IP VPS | `203.0.113.45` | hPanel → VPS → Overview |
| User SSH VPS | `root` | hPanel → VPS → Overview |
| Port SSH | `22` | biasanya 22 |
| Satu subdomain | `pdf-extract.sinemaku.com` | dibuat di langkah A2 |
| Email aktif | `email@anda.com` | untuk sertifikat TLS |

---

## Bagian A — Persiapan

### A1. Pastikan bisa masuk ke VPS (dari Mac)

Buka **Terminal di Mac**, ketik:

```sh
ssh root@203.0.113.45
```

> Ganti `root` dan IP dengan milik Anda. Bila port SSH bukan 22, tambahkan `-p`, misalnya `ssh -p 65002 root@203.0.113.45`.

Bila muncul `Are you sure you want to continue connecting (yes/no)?`, ketik `yes` lalu Enter.

**Output yang diharapkan:** prompt berubah menjadi seperti `root@vps-xxxx:~#`.

Tinggalkan terminal ini terbuka — inilah "terminal VPS" yang dipakai di Bagian B dan C. Ketik `exit` bila ingin keluar.

**Bila gagal:** periksa IP, port, dan user SSH di hPanel → VPS → Overview.

### A2. Buat subdomain untuk layanan ini (DNS)

Layanan ini harus diakses lewat HTTPS, jadi butuh nama domain sendiri. Sepanjang panduan dipakai contoh **`pdf-extract.sinemaku.com`** — ganti dengan milik Anda.

Di tempat domain Anda dikelola (bisa Hostinger → Domains → DNS Zone), tambahkan:

| Type | Name | Value |
|---|---|---|
| A | `pdf-extract` | IP VPS Anda |

Simpan, lalu uji dari Mac:

```sh
dig +short pdf-extract.sinemaku.com
```

**Output yang diharapkan:** IP VPS Anda, misalnya `203.0.113.45`.

**Bila kosong:** DNS belum tersebar. Tunggu 5–15 menit lalu ulangi. Jangan lanjut ke Bagian C sebelum langkah ini menghasilkan IP.

---

## Bagian B — Pasang layanan di VPS

Bagian ini hanya **dua langkah**: salin satu berkas, lalu jalankan satu skrip yang mengerjakan sisanya.

### B1. Salin dua berkas dari Mac ke VPS

Di **Terminal Mac** (bukan di dalam SSH), jalankan:

```sh
cd "/Users/mumuraihan/Documents/codes/sinemaku"
ssh root@203.0.113.45 "mkdir -p /opt/sinemaku-pdf-extract"
scp scripts/pdf-extract-service.cjs scripts/install-pdf-extract-vps.sh root@203.0.113.45:/opt/sinemaku-pdf-extract/
```

> Ganti `root` dan IP sesuai A1. Bila port bukan 22, tambahkan `-P 65002` **tepat setelah** `scp`.

**Output yang diharapkan:** dua baris `100%` lalu kembali ke prompt.

### B2. Jalankan pemeriksaan awal di VPS

Kembali ke **terminal VPS** dari A1, lalu:

```sh
sh /opt/sinemaku-pdf-extract/install-pdf-extract-vps.sh --check
```

Perintah ini **tidak mengubah apa pun**; hanya memeriksa prasyarat.

**Output yang diharapkan** (di VPS baru yang bersih):

```text
== Pemeriksaan prasyarat ==
GAGAL pdftotext tidak ditemukan
GAGAL node tidak ditemukan
OK    systemctl ditemukan: /usr/bin/systemctl
OK    Berkas layanan ada: /opt/sinemaku-pdf-extract/pdf-extract-service.cjs

Ada 2 masalah. Perbaiki dulu (lihat pesan GAGAL di atas).
```

> Muncul `GAGAL` di sini **normal dan diharapkan** — artinya Poppler dan Node belum terpasang. Langkah B3 yang memasangnya.

### B3. Jalankan pemasangan

```sh
sh /opt/sinemaku-pdf-extract/install-pdf-extract-vps.sh
```

Skrip ini mengerjakan, berurutan: memasang Poppler dan Node.js bila belum ada, membuat `/opt/sinemaku-pdf-extract/`, membuat berkas environment `/etc/sinemaku-pdf-extract.env`, membuat secret `/etc/sinemaku-pdf-extract.secret` (hanya bila belum ada), membuat unit systemd, menyalakan layanan, lalu health check.

**Output yang diharapkan:**

```text
== Memasang paket sistem ==
== Menyiapkan layanan ==
OK    Folder layanan siap: /opt/sinemaku-pdf-extract
OK    Berkas environment dibuat: /etc/sinemaku-pdf-extract.env (path pdftotext: /usr/bin/pdftotext)
OK    Secret baru dibuat: /etc/sinemaku-pdf-extract.secret (nilai tidak ditampilkan)
      Ambil nilainya dengan: cat /etc/sinemaku-pdf-extract.secret
OK    Unit systemd dibuat: /etc/systemd/system/sinemaku-pdf-extract.service (node: /usr/bin/node)
OK    Layanan aktif.
OK    Health check: {"ok":true,"service":"sinemaku-pdf-extract"}

Selesai. Langkah berikutnya (Bagian D di panduan):
  1. Pasang Nginx + Certbot dan arahkan domain ke port 8791.
  2. Isi PDF_EXTRACT_URL dan PDF_EXTRACT_SECRET pada .env Hostinger.
  3. php artisan config:cache && php artisan xxi:pdf-doctor
```

**Bila muncul `GAGAL Node.js versi ... terlalu tua`:** pasang Node 20 dengan:

```sh
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs
```

lalu ulangi B3.

**Sifat skrip:** aman dijalankan berulang. Secret dan konfigurasi yang sudah ada **tidak** ditimpa, dan secret **tidak pernah dicetak** ke layar.

### B4. Ambil secret dan simpan di password manager

Secret adalah kunci yang membuktikan permintaan benar-benar datang dari aplikasi Anda. Nilainya dipakai nanti di langkah D1.

```sh
cat /etc/sinemaku-pdf-extract.secret
```

**Output yang diharapkan:** satu baris 64 karakter heksadesimal, bentuknya seperti `3f9a...c1e2`.

> ✅ **Salin nilai itu ke password manager Anda sekarang.** Jangan kirim lewat chat, jangan commit ke git, jangan simpan di dalam repo.
>
> Setelah disalin, bersihkan layar:
>
> ```sh
> clear
> ```

### B5. Pastikan layanan hidup

```sh
systemctl status sinemaku-pdf-extract --no-pager
curl -sS http://127.0.0.1:8791/health
```

**Output yang diharapkan:** ada `Active: active (running)`; health mengembalikan `{"ok":true,"service":"sinemaku-pdf-extract"}`.

Layanan ini hidup otomatis lagi bila VPS di-restart, dan otomatis dijalankan ulang bila crash.

---

## Bagian C — Buka akses HTTPS dari Hostinger

Layanan tadi masih hanya bisa diakses dari dalam VPS. Bagian ini membukanya lewat HTTPS dengan Nginx + sertifikat gratis Let's Encrypt.

### C1. Pasang Nginx dan Certbot (di VPS)

```sh
apt-get install -y nginx certbot python3-certbot-nginx
```

### C2. Buat konfigurasi situs (di VPS)

```sh
cat > /etc/nginx/sites-available/sinemaku-pdf-extract <<'EOF'
server {
    listen 80;
    server_name pdf-extract.sinemaku.com;

    client_max_body_size 30m;

    location / {
        proxy_pass http://127.0.0.1:8791;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_read_timeout 120s;
    }
}
EOF
```

> Ganti `pdf-extract.sinemaku.com` dengan subdomain dari A2.

```sh
ln -sf /etc/nginx/sites-available/sinemaku-pdf-extract /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

**Output yang diharapkan:** `syntax is ok` dan `test is successful`.

Arti dua baris penting di dalamnya:

| Baris | Arti |
|---|---|
| `client_max_body_size 30m` | Mengizinkan unggahan PDF sampai 30 MB |
| `proxy_read_timeout 120s` | Memberi waktu proses sampai 2 menit sebelum menyerah |

### C3. Terbitkan sertifikat TLS (di VPS)

```sh
certbot --nginx -d pdf-extract.sinemaku.com --non-interactive --agree-tos -m email@anda.com --redirect
```

> Ganti subdomain dan email. Certbot akan mengubah konfigurasi Nginx agar HTTPS aktif dan HTTP dialihkan otomatis, serta memperbarui sertifikat sendiri ke depannya.

**Output yang diharapkan:** baris `Congratulations! You have successfully enabled HTTPS`.

Uji dari dalam VPS:

```sh
curl -sS https://pdf-extract.sinemaku.com/health
```

**Output yang diharapkan:** `{"ok":true,"service":"sinemaku-pdf-extract"}`

**Bila muncul error sertifikat atau `502`:** periksa bahwa A2 sudah menghasilkan IP, lalu ulangi C3.

### C4. Batasi akses ke Hostinger saja (disarankan)

Dengan ini, walau URL-nya diketahui orang lain, permintaan dari luar ditolak lebih awal.

Cari IP keluar Hostinger:

```sh
dig +short sinemakupicturesreporting.com | tail -1
```

Ubah blok `location /` di `/etc/nginx/sites-available/sinemaku-pdf-extract` (bisa dengan `nano`), tambahkan dua baris `allow`/`deny` di paling atas blok:

```nginx
    location / {
        allow 203.0.113.90;
        deny all;
        proxy_pass http://127.0.0.1:8791;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_read_timeout 120s;
    }
```

> Ganti `203.0.113.90` dengan IP hasil perintah di atas.

```sh
nginx -t && systemctl reload nginx
```

> Ini lapisan tambahan saja. Penjaga utama tetap tanda tangan HMAC di dalam layanan: tanpa secret yang benar, permintaan tetap ditolak walau IP-nya diizinkan.
>
> **Perhatian:** bila Hostinger mengganti IP keluar server, import akan gagal. Bila itu terjadi, ulangi C4 atau hapus dua baris `allow`/`deny`.

---

## Bagian D — Hubungkan aplikasi di Hostinger

### D1. Tambahkan tiga baris ke `.env`

Buka `.env` aplikasi di Hostinger (hPanel → File Manager, atau SSH), lalu tambahkan di akhir berkas:

```env
PDF_EXTRACT_URL=https://pdf-extract.sinemaku.com
PDF_EXTRACT_SECRET=isi_dengan_secret_dari_langkah_B4
PDF_EXTRACT_TIMEOUT=90
```

> `PDF_EXTRACT_SECRET` harus **sama persis** dengan hasil `cat` di B4: tanpa spasi di sekitar `=`, tanpa tanda kutip, tanpa spasi tambahan di ujung.

Simpan.

### D2. Bersihkan cache konfigurasi (di Hostinger)

```sh
php artisan config:cache
```

**Output yang diharapkan:** `Configuration cached successfully!`

> Setiap kali `.env` diubah, langkah ini **wajib** diulang. Tanpa itu, nilai lama masih dipakai dan perubahan seolah tidak berefek.

### D3. Periksa dari sisi aplikasi (di Hostinger)

```sh
php artisan xxi:pdf-doctor
```

**Output yang diharapkan:**

```text
Mode ekstraksi: layanan VPS (https://pdf-extract.sinemaku.com)
```

**Bila masih tertulis `Mode ekstraksi: binary lokal`:** `.env` belum terbaca. Periksa ejaan nama variabel, pastikan tidak ada spasi, lalu ulangi D2.

---

## Bagian E — Verifikasi akhir (wajib)

### E1. Uji ekstraksi dengan PDF asli (di Hostinger)

Unggah satu laporan XXI ke server (hPanel → File Manager), lalu:

```sh
php artisan xxi:pdf-doctor --pdf=/home/username/laporan-xxi.pdf
```

**Output yang diharapkan** untuk berkas `16MPEA_20260926.pdf`:

```text
OK Ekstraksi berhasil: 534 baris, tanggal 2026-09-26.
```

> Angka `534` dan tanggal `2026-09-26` berasal dari pengujian nyata dengan berkas tersebut. Berkas lain akan menunjukkan angka berbeda — yang penting muncul `OK`, bukan `GAGAL`.

### E2. Uji alur nyata di backoffice

1. Buka Pelaporan → Import XXI.
2. Unggah PDF XXI.
3. Pastikan tabel **preview** muncul dan bisa diedit langsung di tabelnya.
4. Tekan konfirmasi.
5. Periksa laporan masuk dengan benar.

> Preview tidak menulis apa pun ke database. Hanya konfirmasi yang menyimpan data.

### E3. Buktikan kegagalan terbaca jelas (disarankan)

Di VPS, hentikan layanan sementara:

```sh
systemctl stop sinemaku-pdf-extract
```

Ulangi E1. **Yang seharusnya muncul:**

```text
GAGAL Layanan ekstraksi PDF gagal dihubungi (HTTP 502).
```

Nyalakan kembali:

```sh
systemctl start sinemaku-pdf-extract
```

> Ini membuktikan sistem tidak pernah menyimpan data diam-diam ketika ekstraksi gagal — selalu ada pesan penyebabnya.

---

## Perawatan rutin

### Melihat log layanan

```sh
journalctl -u sinemaku-pdf-extract -n 50 --no-pager
```

### Bila layanan mati (misalnya setelah VPS di-restart)

`Restart=on-failure` sudah menyalakannya kembali otomatis, tetapi bila tetap mati:

```sh
systemctl status sinemaku-pdf-extract --no-pager
systemctl start sinemaku-pdf-extract
```

Menjalankan ulang skrip pemasangan di Bagian B3 juga memulihkannya (unit di-`enable --now`, secret lama tidak diubah, dan health check dijalankan lagi):

```sh
sh /opt/sinemaku-pdf-extract/install-pdf-extract-vps.sh
```

### Memperbarui berkas layanan setelah ada perubahan kode

```sh
# di Mac
cd "/Users/mumuraihan/Documents/codes/sinemaku"
scp scripts/pdf-extract-service.cjs root@203.0.113.45:/opt/sinemaku-pdf-extract/
```

```sh
# di VPS
systemctl restart sinemaku-pdf-extract
curl -sS http://127.0.0.1:8791/health
```

### Mengganti secret (rutin, atau bila diduga bocor)

```sh
# 1. VPS: buat secret baru
umask 077
openssl rand -hex 32 > /etc/sinemaku-pdf-extract.secret
chmod 600 /etc/sinemaku-pdf-extract.secret
cat /etc/sinemaku-pdf-extract.secret   # salin, lalu jalankan: clear
systemctl restart sinemaku-pdf-extract
```

```sh
# 2. Hostinger: ganti PDF_EXTRACT_SECRET di .env dengan nilai baru, lalu
php artisan config:cache
php artisan xxi:pdf-doctor --pdf=/path/laporan.pdf
```

---

## Troubleshooting

| Pesan / gejala | Artinya | Yang harus dilakukan |
|---|---|---|
| `pdftotext tidak tersedia pada VPS` | Poppler belum ada atau path salah | Jalankan B3 lagi, lalu `systemctl restart sinemaku-pdf-extract` |
| `GAGAL Layanan ekstraksi PDF gagal dihubungi (HTTP 401)` | Secret berbeda di kedua sisi, atau jam dua server berbeda lebih dari 5 menit | Bandingkan B4 dengan D1; cek jam dengan `date -u` di VPS dan di Hostinger |
| `GAGAL Layanan ekstraksi PDF gagal dihubungi (HTTP 502/503)` | Nginx hidup, layanan ekstraksi mati | `systemctl status sinemaku-pdf-extract --no-pager` lalu `systemctl restart sinemaku-pdf-extract` |
| `GAGAL Layanan ekstraksi PDF tidak dapat dihubungi (https://...)` | Nginx/domain tidak menjawab sama sekali — VPS mati, DNS salah, atau TLS gagal | Cek `dig +short <subdomain>` (A2) dan `systemctl reload nginx` di VPS |
| `GAGAL Layanan ekstraksi PDF gagal dihubungi (HTTP 404)` | `PDF_EXTRACT_URL` salah | Pastikan hanya berisi domain, tanpa `/extract` atau path lain |
| `Layanan ekstraksi menolak PDF ini.` | PDF rusak atau bukan laporan distributor | Pakai berkas asli dari distributor |
| `curl: (7) Failed to connect` di VPS | Layanan tidak berjalan | `journalctl -u sinemaku-pdf-extract -n 50 --no-pager` |
| Health check gagal saat B3, pesan menyebut `journalctl` | Layanan gagal start | Lihat log; biasanya path `pdftotext` atau `node` berbeda dari dugaan |
| Import masih memakai jalur lama | Kode terbaru belum di-deploy, atau config belum di-cache | Deploy ulang, lalu jalankan D2 |
| `dig` tidak mengembalikan IP (A2) | DNS belum tersebar | Tunggu 5–15 menit; jangan lanjut ke C3 sebelum berhasil |
| Semua normal tetapi import dari Hostinger ditolak | IP keluar Hostinger berubah (efek C4) | Ulangi C4, atau hapus dua baris `allow`/`deny` |

---

## Yang perlu diketahui sebelum memulai

- Layanan ini **tidak menyimpan** PDF Anda dan tidak mencatat isi berkas. Berkas sementara di `/tmp` dihapus segera setelah ekstraksi.
- Hanya kirim **laporan distributor publik**. Jangan kirim dokumen internal perusahaan.
- Bila VPS mati atau di-restart, import PDF XXI gagal dengan pesan jelas sampai layanan hidup kembali; **import Excel XXI tidak terpengaruh sama sekali**.
- Dipakai bersama oleh semua proses import PDF, jadi biaya VPS tetap sama walau jumlah laporan bertambah.
- Secret adalah satu-satunya penjaga akses. Perlakukan seperti password.

---

## Lampiran — cara manual (bila ingin tanpa skrip)

Langkah ini menggantikan B3. Semua dijalankan di VPS sebagai root.

```sh
apt-get update
apt-get install -y poppler-utils
command -v pdftotext          # catat hasilnya, biasanya /usr/bin/pdftotext

mkdir -p /opt/sinemaku-pdf-extract

umask 077
openssl rand -hex 32 > /etc/sinemaku-pdf-extract.secret
chmod 600 /etc/sinemaku-pdf-extract.secret

cat > /etc/sinemaku-pdf-extract.env <<'EOF'
PDF_EXTRACT_BIND=127.0.0.1
PDF_EXTRACT_PORT=8791
PDF_EXTRACT_SECRET_FILE=/etc/sinemaku-pdf-extract.secret
PDF_EXTRACT_PDFTOTEXT=/usr/bin/pdftotext
PDF_EXTRACT_MAX_BYTES=26214400
PDF_EXTRACT_TIMEOUT_MS=60000
EOF
chmod 600 /etc/sinemaku-pdf-extract.env

cat > /etc/systemd/system/sinemaku-pdf-extract.service <<'EOF'
[Unit]
Description=Sinemaku PDF text extraction service
After=network-online.target

[Service]
Type=simple
User=root
EnvironmentFile=/etc/sinemaku-pdf-extract.env
ExecStart=/usr/bin/node /opt/sinemaku-pdf-extract/pdf-extract-service.cjs
Restart=on-failure
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now sinemaku-pdf-extract.service
curl -sS http://127.0.0.1:8791/health
```

> Bila `ExecStart` gagal, cek path Node dengan `command -v node` dan ganti `/usr/bin/node` sesuai hasilnya.

Arti baris pada berkas environment:

| Baris | Arti |
|---|---|
| `PDF_EXTRACT_BIND=127.0.0.1` | Hanya menerima koneksi dari dalam VPS (akses luar lewat Nginx) |
| `PDF_EXTRACT_PORT=8791` | Port internal |
| `PDF_EXTRACT_SECRET_FILE` | Lokasi kunci |
| `PDF_EXTRACT_PDFTOTEXT` | Path binary hasil `command -v` |
| `PDF_EXTRACT_MAX_BYTES` | Batas ukuran PDF, 25 MiB |
| `PDF_EXTRACT_TIMEOUT_MS` | Batas waktu ekstraksi per berkas, 60 detik |
