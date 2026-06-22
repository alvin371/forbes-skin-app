#!/bin/sh
# Container entrypoint: apply any pending DB migrations, then start the app.
#
# Why: deploys ship code but historically never ran migrations, so committed
# index/schema changes silently never took effect. This applies them on start.
#
# Non-blocking by design: if the migration step fails or times out, we LOG and
# still start the app (a missing index degrades performance; a blocked start is
# an outage). The runner is advisory-locked, so concurrent replicas don't race.

set -e

RUNNER="/var/www/html/migrations/run.php"
if [ -f "$RUNNER" ]; then
    echo "[entrypoint] applying pending migrations..."
    if command -v timeout >/dev/null 2>&1; then
        timeout 120 php "$RUNNER" --pending || echo "[entrypoint] WARN: migrations --pending failed/timed out; starting app anyway"
    else
        php "$RUNNER" --pending || echo "[entrypoint] WARN: migrations --pending failed; starting app anyway"
    fi
fi

exec "$@"
