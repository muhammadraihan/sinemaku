#!/bin/sh
set -eu
# Used by systemd with EnvironmentFile already loaded; cron loads its protected file.
if [ "${CINEPOINT_NODE_BINARY:-}" = "" ]; then
    set -a
    . /etc/sinemaku-cinepoint.env
    set +a
fi
cd /home/ubuntu/cinepoint
exec /usr/bin/flock -w 180 .worker.lock "$CINEPOINT_NODE_BINARY" /home/ubuntu/cinepoint/scripts/cinepoint-push-snapshot.cjs "$@"
