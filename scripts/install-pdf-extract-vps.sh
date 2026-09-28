#!/bin/sh
# Sinemaku PDF extraction service — VPS installer.
#
# Installs Poppler and prepares the extraction service (env, secret, systemd unit)
# then runs a health check. TLS/reverse proxy stays manual: see
# docs/pdf-vps-extraction-deployment.md (Bagian D).
#
# Usage (as root on the VPS):
#   sh install-pdf-extract-vps.sh --check          # verify prerequisites, change nothing
#   sh install-pdf-extract-vps.sh                  # install and start
#
# The secret is generated if absent and NEVER printed. Retrieve it yourself with:
#   cat /etc/sinemaku-pdf-extract.secret
#
# Idempotent: safe to re-run; existing secret and configuration are preserved.

set -eu

SERVICE_NAME="sinemaku-pdf-extract"
INSTALL_DIR="/opt/${SERVICE_NAME}"
ENV_FILE="/etc/${SERVICE_NAME}.env"
SECRET_FILE="/etc/${SERVICE_NAME}.secret"
UNIT_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
SERVICE_SCRIPT="${INSTALL_DIR}/pdf-extract-service.cjs"
PORT="8791"

MODE="install"
[ "${1:-}" = "--check" ] && MODE="check"

say() { printf '%s\n' "$*"; }
ok() { printf 'OK    %s\n' "$*"; }
bad() { printf 'GAGAL %s\n' "$*"; }

failures=0

require_root() {
    if [ "$(id -u)" -ne 0 ]; then
        say "Skrip ini harus dijalankan sebagai root. Contoh: sudo sh $0"
        exit 1
    fi
}

check_binary() {
    if command -v "$1" >/dev/null 2>&1; then
        ok "$1 ditemukan: $(command -v "$1")"
        return 0
    fi
    bad "$1 tidak ditemukan"
    failures=$((failures + 1))
    return 1
}

install_packages() {
    say ""
    say "== Memasang paket sistem =="
    if ! command -v pdftotext >/dev/null 2>&1; then
        apt-get update
        apt-get install -y poppler-utils
    fi
    if ! command -v node >/dev/null 2>&1; then
        curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
        apt-get install -y nodejs
    fi
}

run_checks() {
    say "== Pemeriksaan prasyarat =="
    check_binary pdftotext || true
    check_binary node || true
    if command -v node >/dev/null 2>&1; then
        major=$(node -p 'process.versions.node.split(".")[0]' 2>/dev/null || echo 0)
        if [ "$major" -ge 18 ] 2>/dev/null; then
            ok "Node.js versi $(node -v) memenuhi syarat (minimal 18)."
        else
            bad "Node.js versi $(node -v) terlalu tua; minimal 18."
            failures=$((failures + 1))
        fi
    fi
    check_binary systemctl || true
    if [ -f "$SERVICE_SCRIPT" ]; then
        ok "Berkas layanan ada: $SERVICE_SCRIPT"
    else
        bad "Berkas layanan belum ada di $SERVICE_SCRIPT (salin dulu dengan scp)."
        failures=$((failures + 1))
    fi
}

write_env_file() {
    if [ -f "$ENV_FILE" ]; then
        ok "Berkas environment sudah ada, dibiarkan: $ENV_FILE"
        return
    fi
    pdftotext_path=$(command -v pdftotext)
    cat > "$ENV_FILE" <<EOF
PDF_EXTRACT_BIND=127.0.0.1
PDF_EXTRACT_PORT=${PORT}
PDF_EXTRACT_SECRET_FILE=${SECRET_FILE}
PDF_EXTRACT_PDFTOTEXT=${pdftotext_path}
PDF_EXTRACT_MAX_BYTES=26214400
PDF_EXTRACT_TIMEOUT_MS=60000
EOF
    chmod 600 "$ENV_FILE"
    ok "Berkas environment dibuat: $ENV_FILE (path pdftotext: ${pdftotext_path})"
}

write_secret() {
    if [ -f "$SECRET_FILE" ]; then
        ok "Secret sudah ada, dibiarkan: $SECRET_FILE"
        return
    fi
    umask 077
    openssl rand -hex 32 > "$SECRET_FILE"
    chmod 600 "$SECRET_FILE"
    ok "Secret baru dibuat: $SECRET_FILE (nilai tidak ditampilkan)"
    say "      Ambil nilainya dengan: cat $SECRET_FILE"
}

write_unit() {
    node_path=$(command -v node)
    cat > "$UNIT_FILE" <<EOF
[Unit]
Description=Sinemaku PDF text extraction service
After=network-online.target

[Service]
Type=simple
User=root
EnvironmentFile=${ENV_FILE}
ExecStart=${node_path} ${SERVICE_SCRIPT}
Restart=on-failure
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
    ok "Unit systemd dibuat: $UNIT_FILE (node: ${node_path})"
}

start_service() {
    systemctl daemon-reload
    systemctl enable --now "${SERVICE_NAME}.service"
    sleep 1
    if systemctl is-active --quiet "${SERVICE_NAME}.service"; then
        ok "Layanan aktif."
    else
        bad "Layanan tidak aktif. Jalankan: systemctl status ${SERVICE_NAME} --no-pager"
        failures=$((failures + 1))
    fi
    if [ -f "$SECRET_FILE" ]; then
        health=""
        attempt=1
        while [ "$attempt" -le 10 ]; do
            health=$(curl -sS --max-time 5 "http://127.0.0.1:${PORT}/health" 2>/dev/null || true)
            case "$health" in
                *'"ok":true'*) break ;;
            esac
            sleep 1
            attempt=$((attempt + 1))
        done
        case "$health" in
            *'"ok":true'*) ok "Health check: ${health}" ;;
            *) bad "Health check gagal setelah 10 percobaan. Jawaban: ${health:-<kosong>}"
               say "      Periksa: journalctl -u ${SERVICE_NAME} -n 50 --no-pager"
               failures=$((failures + 1)) ;;
        esac
    fi
}

main() {
    run_checks

    if [ "$MODE" = "check" ]; then
        say ""
        if [ "$failures" -eq 0 ]; then
            say "Semua prasyarat terpenuhi. Jalankan tanpa --check untuk memasang."
        else
            say "Ada $failures masalah. Perbaiki dulu (lihat pesan GAGAL di atas)."
        fi
        [ "$failures" -eq 0 ] || exit 1
        exit 0
    fi

    require_root
    install_packages
    say ""
    say "== Menyiapkan layanan =="
    mkdir -p "$INSTALL_DIR"
    ok "Folder layanan siap: $INSTALL_DIR"

    if [ ! -f "$SERVICE_SCRIPT" ]; then
        bad "Berkas layanan belum ada di $SERVICE_SCRIPT."
        say "      Dari komputer Anda, jalankan:"
        say "      scp scripts/pdf-extract-service.cjs root@<IP-VPS>:${INSTALL_DIR}/"
        exit 1
    fi

    write_env_file
    write_secret
    write_unit
    start_service

    say ""
    if [ "$failures" -eq 0 ]; then
        say "Selesai. Langkah berikutnya (Bagian D di panduan):"
        say "  1. Pasang Nginx + Certbot dan arahkan domain ke port ${PORT}."
        say "  2. Isi PDF_EXTRACT_URL dan PDF_EXTRACT_SECRET pada .env Hostinger."
        say "  3. php artisan config:cache && php artisan xxi:pdf-doctor"
    else
        say "Selesai dengan $failures masalah. Baca pesan GAGAL di atas."
        exit 1
    fi
}

main
