# Cinepoint daily scheduler deployment

## Runtime requirement — read before deployment

Collector Cinepoint membaca seluruh pagination dari DOM browser publik. Ia **bukan** job PHP-only dan tidak dapat berjalan jika server hanya memiliki PHP/Laravel.

Runtime yang wajib tersedia pada **host yang menjalankan command collector**:

- Node.js (`node --version`)
- npm (`npm --version`)
- dependency project (`npm ci`)
- Chrome atau Chromium executable yang dapat diluncurkan oleh user cron
- akses HTTPS keluar ke `https://cinepoint.com/`

Jika menjalankan:

```sh
node scripts/cinepoint-daily-browser.cjs
```

lalu muncul:

```text
node: command not found
```

maka collector **belum dapat dijalankan** pada host tersebut. Jangan memasang cron `cinepoint:collect-daily` sebelum semua requirement di atas tersedia; setiap cron hanya akan gagal dan mencatat error.

## Pilih deployment yang sesuai

### A. Server dengan runtime browser yang telah diverifikasi

VPS dengan izin instalasi software dapat dikonfigurasi untuk collector. Jangan menyamakan paket Cloud/Node.js hosting dengan VPS: adanya Node.js saja tidak membuktikan dukungan Chrome, library OS, atau subprocess PHP. Verifikasi kemampuan paket dengan provider terlebih dahulu.

```sh
node --version
npm --version
command -v google-chrome || command -v chromium || command -v chromium-browser
```

Jika Node.js memang belum ada tetapi Anda memiliki akses memasang software, install melalui metode yang didukung server/provider. Contoh NVM untuk shell Linux yang mengizinkannya:

```sh
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.7/install.sh | bash
source ~/.bashrc
nvm install --lts
nvm use --lts
node --version
npm --version
```

Lalu pastikan Chrome/Chromium tersedia untuk user yang sama dengan user cron. Path executable dapat berbeda pada tiap OS/provider.

> Jangan jalankan perintah instalasi ini di shared hosting tanpa memastikan Hostinger mengizinkannya. Banyak paket shared hosting tidak mengizinkan service browser/headless Chrome berjalan terus-menerus.

### B. Hostinger shared hosting tanpa Node.js/Chrome

Shared hosting tersebut dapat tetap menjalankan Laravel, UI, database, dan cron PHP, tetapi **tidak dapat menjalankan collector browser Cinepoint**.

Gunakan satu runtime collector terpisah yang mendukung Node.js + Chrome/Chromium, misalnya:

- VPS kecil;
- Hostinger Cloud/VPS yang menyediakan Node dan browser runtime;
- managed browser/worker yang memang mendukung Playwright;
- server internal/worker yang selalu tersedia.

Aplikasi Sinemaku tetap dapat di shared hosting, tetapi collector perlu dikonfigurasikan untuk mengirim snapshot tervalidasi ke aplikasi melalui endpoint internal yang diautentikasi. Endpoint HMAC dan polling VPS telah diimplementasikan; gunakan [panduan VPS → Hostinger](cinepoint-vps-hostinger-deployment.md). Jangan gunakan jadwal lokal di bawah pada Hostinger; `CINEPOINT_MODE=remote` menonaktifkannya.

## Application setup on a compatible collector host

```sh
cd /path/to/sinemaku
composer install --no-dev --optimize-autoloader
npm ci
node --version
npm --version
command -v google-chrome || command -v chromium || command -v chromium-browser
node scripts/cinepoint-daily-browser.cjs > /tmp/cinepoint.json
php artisan cinepoint:collect-daily
```

Verifikasi output browser sebelum menjadwalkan:

```sh
python3 - <<'PY'
import json
p = json.load(open('/tmp/cinepoint.json'))
print({
  'source_total': p['source_total'],
  'entries': len(p['entries']),
  'unique_ids': len({row['source_movie_id'] for row in p['entries']}),
})
PY
```

`source_total`, `entries`, dan `unique_ids` harus sama. Jangan deploy jika ada row kurang, ID duplikat, atau output tidak valid.

Jika path runtime tidak standar, konfigurasi environment server:

```env
CINEPOINT_NODE_BINARY=/absolute/path/to/node
CINEPOINT_BROWSER_SCRIPT=/absolute/path/to/sinemaku/scripts/cinepoint-daily-browser.cjs
# Gunakan salah satu saja:
CINEPOINT_BROWSER_EXECUTABLE=/usr/bin/chromium
# CINEPOINT_BROWSER_CHANNEL=chrome
```

Setelah mengubah environment:

```sh
php artisan config:clear
php artisan config:cache
php artisan view:cache
```

## Laravel scheduler

Aplikasi telah menjadwalkan collection pada:

```text
07:00 WIB
12:00 WIB
18:00 WIB
Timezone: Asia/Jakarta
```

Jalankan Laravel scheduler setiap menit **hanya pada host yang memiliki runtime browser lengkap**:

```cron
* * * * * cd /path/to/sinemaku && /absolute/path/to/php artisan schedule:run >> /path/to/sinemaku/storage/logs/scheduler.log 2>&1
```

Cari binary PHP yang benar:

```sh
command -v php
php -v
php artisan schedule:list
php artisan schedule:run -v
```

Pastikan `storage/` dan `bootstrap/cache/` writable oleh user cron.

## Manual run and diagnosis

```sh
php artisan cinepoint:collect-daily
php artisan schedule:list
php artisan schedule:run -v
php artisan optimize:clear
```

Command berstatus non-zero apabila Node/Chrome tidak tersedia, browser gagal, source tidak lengkap, ada ID duplikat, parser berubah, atau verifikasi database gagal. Snapshot sukses sebelumnya tidak diganti jika percobaan baru gagal. Halaman admin `/backoffice/audience-estimate` menampilkan error terakhir dan menyediakan tombol **Sync sekarang**.

Periksa:

```sh
node --version
npm --version
command -v google-chrome || command -v chromium || command -v chromium-browser
tail -n 100 storage/logs/laravel.log
tail -n 100 storage/logs/scheduler.log
```

## Rollback

Backup kode seat-map TIX/Cinepolis yang telah dihapus berada di:

```text
/Users/mumuraihan/.hermes/backups/sinemaku-seatmap-tix-20260918
```

Migration dan data lama tidak dihapus. Modul import laporan/distributor Cinepolis tidak diubah.
