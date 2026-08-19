#!/usr/bin/env bash
# Prove — against the RUNNING containers, not the config files — that the load-test stack
# cannot reach production. Run by up.sh, and standalone before any real-provider stage.
#
# Every check is a positive proof of isolation, not an absence of evidence. Prints only
# PASS/FAIL and check names; never a hostname, credential or connection string.

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

# Mock runs must have NO route off the host. Staged real-provider runs need one by
# definition, so egress is asserted explicitly rather than silently tolerated.
EXPECT_EGRESS=0
while [ $# -gt 0 ]; do
  case "$1" in
    --expect-egress) EXPECT_EGRESS=1; shift ;;
    *) lt_die "unknown argument: $1" ;;
  esac
done

FAILED=0
pass() { printf '  PASS  %s\n' "$1"; }
fail() { printf '  FAIL  %s\n' "$1"; FAILED=1; }

# Everything runs in a one-off container built from the SAME image and joined to the SAME
# network as a real worker, so this proves the environment a worker would get — and works
# before any worker has been started.

# 1. The .env a worker sees is the load-test one, not the production one. This is the single
#    most important check: env_helper.php prefers FCPATH.'.env' over getenv(), so if the
#    mount silently failed the container would be reading production configuration.
if lt_worker_once grep -q '^ENDORSE_REFRESH_LOADTEST=1$' /var/www/html/.env >/dev/null 2>&1; then
  pass "worker .env is the load-test file (mount took effect)"
else
  fail "worker .env is NOT the load-test file - the mount did not take effect"
fi

# 2. The database it would connect to carries the disposable suffix.
ACTUAL_DB="$(lt_worker_once sh -c "sed -n 's/^DB_DATABASE=//p' /var/www/html/.env | tail -1" 2>/dev/null | tr -d '\r\n' || true)"
case "$ACTUAL_DB" in
  *_loadtest) pass "database name carries the _loadtest suffix" ;;
  *)          fail "database name does not carry the _loadtest suffix" ;;
esac

# 3. The guard itself agrees — and it runs in the worker's own constructor, BEFORE
#    CI_Controller autoloads the database. `check` reaching stdout at all is the proof.
if lt_worker_once php index.php EndorseRefreshWorker check 2>/dev/null | grep -q 'isolation proven'; then
  pass "EndorseRefreshLoadTestGuard reports isolation proven"
else
  fail "EndorseRefreshLoadTestGuard refuses this environment"
  lt_worker_once php index.php EndorseRefreshWorker check 2>&1 >/dev/null | sed 's/^/      /' || true
fi

# 4. Production MySQL must not be reachable from the worker's network namespace.
#
#    A default Docker bridge NATs outbound traffic, so this check FAILS on a naive setup —
#    which is precisely why the base network is `internal: true`. In egress mode the route
#    exists by design, so the assertion narrows to "the production DB port specifically is
#    not answering us", and the config guard remains the thing preventing a connection.
if lt_worker_once timeout 4 bash -c 'exec 3<>/dev/tcp/217.217.253.76/3306' >/dev/null 2>&1; then
  if [ "$EXPECT_EGRESS" -eq 1 ]; then
    fail "production MySQL is reachable AND answering, even in egress mode"
  else
    fail "production MySQL is REACHABLE - the load-test network is not internal"
  fi
else
  pass "production MySQL is not reachable from the worker network"
fi

# 4b. In mock mode, prove there is no route off the host AT ALL — not merely that one host
#     is unreachable. This is the difference between "we did not find a way out" and "there
#     is no way out".
if [ "$EXPECT_EGRESS" -eq 0 ]; then
  if lt_worker_once timeout 4 bash -c 'exec 3<>/dev/tcp/1.1.1.1/443' >/dev/null 2>&1; then
    fail "the worker network has internet egress - mock runs must be air-gapped"
  else
    pass "the worker network has no internet egress (mock mode)"
  fi
else
  pass "egress expected for a staged real-provider run (skipping air-gap assertion)"
fi

# 5. The WORKER's own network attachment — the one that decides what it can reach.
#    loadtest-mysql and provider-mock are also on forbes-loadtest-control so the host can
#    reach their published ports; the worker must never be, and must never be on
#    hrms-network or any deployed stack network.
WORKER_NETS="$(lt_compose run --rm --entrypoint "" -d endorse-worker sleep 5 2>/dev/null | tail -1)"
if [ -n "$WORKER_NETS" ]; then
  NETS="$(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$WORKER_NETS" 2>/dev/null || echo '')"
  docker rm -f "$WORKER_NETS" >/dev/null 2>&1 || true

  UNEXPECTED="$(printf '%s' "$NETS" | tr ' ' '\n' | grep -v '^$' | grep -v '^forbes-loadtest-net$' | grep -v "^forbes-loadtest-egress$" || true)"
  if [ -z "$UNEXPECTED" ]; then
    pass "worker is attached only to the isolated load-test network"
  else
    fail "worker is attached to an unexpected network: ${UNEXPECTED}"
  fi

  if [ "$EXPECT_EGRESS" -eq 0 ] && printf '%s' "$NETS" | grep -q 'forbes-loadtest-control'; then
    fail "worker is on the host-facing control network - it must not be"
  fi
else
  fail "could not inspect the worker's network attachment"
fi

# 6. Mail/webhook/push/Sentry credentials are absent from the worker environment entirely.
if lt_worker_once grep -Eq '^(SMTP_PASS|TELEGRAM_BOT_TOKEN|FCM_SERVICE_ACCOUNT_B64|SENTRY_DSN)=.+' /var/www/html/.env >/dev/null 2>&1; then
  fail "outbound notification credentials are present in the worker environment"
else
  pass "no mail/webhook/push/Sentry credentials in the worker environment"
fi

if [ "$FAILED" -ne 0 ]; then
  lt_die "isolation is NOT proven - refusing to proceed"
fi

lt_say "isolation proven"
