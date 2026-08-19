#!/usr/bin/env bash
# Bring up the disposable load-test stack: TLS material, containers, schema.
# Idempotent — safe to re-run.
#
#   tools/loadtest/up.sh [--replicas N]

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

REPLICAS=1
while [ $# -gt 0 ]; do
  case "$1" in
    --replicas) REPLICAS="${2:?--replicas needs a number}"; shift 2 ;;
    *) lt_die "unknown argument: $1" ;;
  esac
done

lt_require_env_file
mkdir -p "$LT_CERTS" "$LT_REPORTS"

# --- TLS for the mock --------------------------------------------------------
# Template::buildRapidApiUrl() hardcodes https:// and nothing overrides SSL_VERIFYPEER, so
# the mock has to present a certificate the worker image trusts. Generating our own CA is
# the minimal seam: no PHP change, and the trust is scoped to the load-test containers.
if [ ! -f "${LT_CERTS}/mock.crt" ]; then
  lt_say "generating self-signed CA + mock certificate"
  openssl req -x509 -newkey rsa:2048 -nodes -days 365 \
    -keyout "${LT_CERTS}/mock-ca.key" -out "${LT_CERTS}/mock-ca.crt" \
    -subj "/CN=forbes-loadtest-mock-ca" >/dev/null 2>&1

  openssl req -newkey rsa:2048 -nodes \
    -keyout "${LT_CERTS}/mock.key" -out "${LT_CERTS}/mock.csr" \
    -subj "/CN=provider-mock.local" >/dev/null 2>&1

  # Both aliases must be in the SAN, or the direct-scrape leg fails hostname verification
  # and gets classified infra_tls — which looks exactly like a provider outage.
  cat > "${LT_CERTS}/mock.ext" <<'EOF'
subjectAltName = DNS:provider-mock.local, DNS:tiktok-mock.local, DNS:localhost, IP:127.0.0.1
extendedKeyUsage = serverAuth
EOF

  openssl x509 -req -in "${LT_CERTS}/mock.csr" \
    -CA "${LT_CERTS}/mock-ca.crt" -CAkey "${LT_CERTS}/mock-ca.key" -CAcreateserial \
    -out "${LT_CERTS}/mock.crt" -days 365 -extfile "${LT_CERTS}/mock.ext" >/dev/null 2>&1

  chmod 644 "${LT_CERTS}"/*.crt "${LT_CERTS}"/*.key
fi

# --- Stack -------------------------------------------------------------------
# Workers stay at 0. They are started by run.sh, after the schema and dataset exist —
# otherwise a scenario would begin against a half-migrated database and report a throughput
# number that means nothing.
lt_say "starting infrastructure (project: ${LT_PROJECT})"
lt_compose up -d --build loadtest-mysql provider-mock

lt_wait_healthy loadtest-mysql 60

# --- Prove isolation before a single row is written --------------------------
lt_say "verifying isolation"
"${LT_DIR}/verify-isolation.sh"

# Reuses the integration suite's canonical schema builder: the real endorse/campaign dumps
# plus the real migration up() paths, in order. `migrations/run.php --pending` cannot be used
# on a bare database — the migration set assumes a business schema it does not create.
lt_say "building schema in ${LT_DB_NAME}"
lt_worker_once php tools/loadtest/schema.php

lt_say "up. next: tools/loadtest/seed.sh --count 13500"
