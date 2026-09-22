# Cinepoint VPS → Hostinger secure ingestion

## Boundary
Hostinger only stores/serves snapshots. In `CINEPOINT_MODE=remote`, Laravel never schedules or runs Node/Chromium. The authenticated backoffice **Sync sekarang** action creates or coalesces one durable request; it is not a local collection success. The VPS polls Hostinger every minute, atomically leases a request, collects, then posts its result. No inbound VPS service or SSH trigger is used.

## Secret setup (do not paste a secret into chat/history)
Generate once on an administrator workstation, retaining the value only in the protected file:

```sh
umask 077
openssl rand -hex 32 > /secure/cinepoint-ingest.secret
chmod 600 /secure/cinepoint-ingest.secret
```

Place the exact same value through Hostinger’s protected environment mechanism and in the VPS protected file. Do not create a repository secret artifact and do not put the value in systemd units, command lines, logs, or this document.

Hostinger environment:

```env
CINEPOINT_MODE=remote
CINEPOINT_INGEST_KEY_ID=cinepoint-vps-1
CINEPOINT_INGEST_SECRET=<same protected value>
CINEPOINT_LEASE_SECONDS=600
CINEPOINT_MAX_ATTEMPTS=3
```

Deploy Laravel code, then run only the listed migrations and refresh cached configuration:

```sh
php artisan migrate --path=database/migrations/2026_09_19_000001_add_cinepoint_remote_collection_contract.php --force
php artisan migrate --path=database/migrations/2026_09_19_000002_create_cinepoint_collector_deliveries.php --force
php artisan config:cache
php artisan route:list --path=api/internal/cinepoint
```

## Signed API contract
All are JSON `POST` requests under `/api/internal/cinepoint`: `snapshots`, `sync-jobs/claim`, and `sync-jobs/{id}/result`.

Headers: `X-Cinepoint-Key-Id`, `X-Cinepoint-Timestamp` (Unix seconds), `X-Cinepoint-Delivery` (unique 32 lowercase hex chars), `X-Cinepoint-Body-Sha256`, `X-Cinepoint-Signature` (lowercase SHA-256 HMAC).

The canonical HMAC string is exactly:

```text
METHOD\n/PATH\nTIMESTAMP\nDELIVERY\nSHA256(raw request body)
```

The method/path binding prevents cross-endpoint replay. Hostinger accepts only the configured key id, valid body hash/signature, a five-minute clock window, 2 MiB max JSON body, and HTTPS in production. Delivery IDs are permanent idempotency records: an identical replay gets the stored response; different body/path with the same delivery gets 409. Secrets are never logged or returned.

Snapshots require exact source count, unique IDs, contiguous ranks, strict `M j, Y` date, bounded nonnegative admissions, titles, and optional poster URLs only at `https://cinepoint-assets.s3.amazonaws.com`.

## VPS files and environment
Copy all three files into `/home/ubuntu/cinepoint/scripts/`, keeping the existing layout. Dependencies remain in `/home/ubuntu/cinepoint/node_modules/`. Determine binaries on the VPS; do not hard-code an NVM path:

```sh
command -v node
command -v chromium || command -v chromium-browser || command -v google-chrome
```

Create `/etc/sinemaku-cinepoint.env` as root, mode 600:

```env
CINEPOINT_API_BASE_URL=https://sinemakupicturesreporting.com/api/internal/cinepoint
CINEPOINT_INGEST_KEY_ID=cinepoint-vps-1
CINEPOINT_INGEST_SECRET_FILE=/etc/sinemaku-cinepoint.secret
CINEPOINT_WORKER_ID=cinepoint-vps-1
CINEPOINT_NODE_BINARY=/absolute/path/from-command-v-node
CINEPOINT_BROWSER_SCRIPT=/home/ubuntu/cinepoint/scripts/cinepoint-daily-browser.cjs
CINEPOINT_BROWSER_EXECUTABLE=/snap/bin/chromium
```

Create `/etc/sinemaku-cinepoint.secret` from the private transfer, `chmod 600`, owned by `ubuntu`. The environment file is root-owned (systemd reads it before changing user); the secret is ubuntu-owned mode 600. Do not enable verbose request logging.

`/etc/systemd/system/sinemaku-cinepoint-poll.service`:

```ini
[Unit]
Description=Sinemaku Cinepoint VPS poll worker
[Service]
Type=oneshot
User=ubuntu
WorkingDirectory=/home/ubuntu/cinepoint
EnvironmentFile=/etc/sinemaku-cinepoint.env
ExecStart=/bin/sh /home/ubuntu/cinepoint/scripts/run-and-push.sh --poll
TimeoutStartSec=480
```

`/etc/systemd/system/sinemaku-cinepoint-poll.timer`:

```ini
[Unit]
[Timer]
OnCalendar=*:0/1
Persistent=true
[Install]
WantedBy=timers.target
```

`/etc/systemd/system/sinemaku-cinepoint-refresh.service` is identical except `ExecStart` omits `--poll`; `sinemaku-cinepoint-refresh.timer`:

```ini
[Unit]
[Timer]
OnCalendar=*-*-* 07,12,18:00:00 Asia/Jakarta
Persistent=true
[Install]
WantedBy=timers.target
```

Enable only after local VPS tests:

```sh
sudo systemctl daemon-reload
sudo systemctl enable --now sinemaku-cinepoint-poll.timer sinemaku-cinepoint-refresh.timer
systemctl list-timers 'sinemaku-cinepoint-*'
```

Cron alternative (not together with timers): set the VPS timezone only with operator approval (`sudo timedatectl set-timezone Asia/Jakarta`), use root crontab below (the protected env is loaded before dropping to ubuntu):

```cron
* * * * * . /etc/sinemaku-cinepoint.env; export CINEPOINT_NODE_BINARY CINEPOINT_API_BASE_URL CINEPOINT_INGEST_KEY_ID CINEPOINT_INGEST_SECRET_FILE CINEPOINT_WORKER_ID CINEPOINT_BROWSER_SCRIPT CINEPOINT_BROWSER_EXECUTABLE; /usr/sbin/runuser -u ubuntu -- /bin/sh /home/ubuntu/cinepoint/scripts/run-and-push.sh --poll
0 7,12,18 * * * . /etc/sinemaku-cinepoint.env; export CINEPOINT_NODE_BINARY CINEPOINT_API_BASE_URL CINEPOINT_INGEST_KEY_ID CINEPOINT_INGEST_SECRET_FILE CINEPOINT_WORKER_ID CINEPOINT_BROWSER_SCRIPT CINEPOINT_BROWSER_EXECUTABLE; /usr/sbin/runuser -u ubuntu -- /bin/sh /home/ubuntu/cinepoint/scripts/run-and-push.sh
```

Manual run after configuration: `sudo systemctl start sinemaku-cinepoint-refresh.service`. Never source the environment with shell tracing enabled.

## Rotation and recovery
Stop both VPS timers, replace key ID and the same secret securely on both hosts, run Hostinger `config:cache`, verify a signed poll, then restart timers. Only one active key is supported; old-key requests fail closed. Keep `APP_KEY` stable: delivery responses (including leases) are encrypted at rest. Do not delete delivery rows to retry. The worker retries transport/5xx/429 three times with identical bytes and delivery and fresh timestamps. After a process crash, a manual job is recoverable through lease expiry; a scheduled upload must be rerun (no durable local outbox). Identical snapshots deduplicate through the database fingerprint. Do not use the obsolete `cinepoint-upload.cjs` protocol.

## Pre-production verification
On VPS: browser JSON must have equal `source_total`, entry count, and unique IDs; run worker `--poll` only after a backoffice request. On Hostinger verify expected 201/200 data, request status, snapshot/entry counts, and the dashboard’s stored worker status. Do not test against production until the secret and deployment are explicitly configured. A failed/expired lease retries up to three times; expired exhausted leases become safe `max_attempts_exceeded` failures and free the manual-sync slot.
