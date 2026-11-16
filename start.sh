#!/bin/bash
set -euo pipefail

export PORT=${PORT:-8080}
echo "[INFO] Starting Sparkleen Laundry on port $PORT"

# Apply migrations only when the operator explicitly enables this release step.
# Back up and validate a restore before setting this flag. A failed migration
# must stop startup instead of publishing a partially migrated application.
if [ "${RUN_DATABASE_MIGRATIONS:-0}" = "1" ]; then
    echo "[INFO] Running explicitly enabled database migrations..."
    php spark migrate --all
fi

# Seeders are manual provisioning commands; never create default accounts on boot.
# php:apache must use prefork rather than a conflicting threaded MPM.
rm -f /etc/apache2/mods-enabled/mpm_event.load \
    /etc/apache2/mods-enabled/mpm_event.conf \
    /etc/apache2/mods-enabled/mpm_worker.load \
    /etc/apache2/mods-enabled/mpm_worker.conf

exec apache2-foreground
