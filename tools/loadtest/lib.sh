#!/usr/bin/env bash
# Shared settings and helpers for the endorse-refresh load-test scripts.
# Sourced, never executed.

set -euo pipefail

LT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LT_DIR="${LT_ROOT}/tools/loadtest"
LT_CERTS="${LT_DIR}/certs"
LT_REPORTS="${LT_DIR}/reports"

# A dedicated project name is the blast radius. Every docker command below is scoped to it,
# so a teardown here can never reach the dev stack in docker-compose.yml.
LT_PROJECT="forbes-loadtest"
LT_COMPOSE_FILE="${LT_ROOT}/docker-compose.loadtest.yml"
LT_ENV_FILE="${LT_ROOT}/.env.loadtest"

# Must match .env.loadtest / the guard. Repeated here because these scripts run BEFORE the
# guard does, and a shell-side mistake must not be the thing that reaches production.
LT_DB_NAME="forbes_loadtest"
LT_DB_PORT="33071"
LT_MOCK_CONTROL_PORT="18080"

lt_compose() { docker compose -p "$LT_PROJECT" -f "$LT_COMPOSE_FILE" "$@"; }

lt_say()  { printf '==> %s\n' "$*"; }
lt_warn() { printf 'WARNING: %s\n' "$*" >&2; }
lt_die()  { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

# Fail before doing anything if the environment file is missing or obviously wrong.
# This duplicates part of EndorseRefreshLoadTestGuard on purpose: the guard runs inside the
# worker, which is too late to stop a script from pointing a MySQL client somewhere real.
lt_require_env_file() {
  [ -f "$LT_ENV_FILE" ] || lt_die ".env.loadtest not found. Run: cp .env.loadtest.example .env.loadtest"

  local db host env_marker ci_env
  db="$(lt_env_value DB_DATABASE)"
  host="$(lt_env_value DB_HOSTNAME)"
  env_marker="$(lt_env_value ENDORSE_REFRESH_LOADTEST)"
  ci_env="$(lt_env_value CI_ENV)"

  case "$db" in
    *_loadtest) ;;
    *) lt_die "DB_DATABASE must end in _loadtest (got a name that does not). Refusing to continue." ;;
  esac
  case "$host" in
    loadtest-mysql|127.0.0.1|localhost|::1|*.local|*.internal) ;;
    *) lt_die "DB_HOSTNAME is not a local/loadtest host. Refusing to continue." ;;
  esac
  [ "$ci_env" = "testing" ] || lt_die "CI_ENV must be 'testing'."
  case "$env_marker" in
    1|true|yes|on) ;;
    *) lt_die "ENDORSE_REFRESH_LOADTEST must be set to 1." ;;
  esac
}

# Read one key from .env.loadtest. Values are never echoed by callers other than the
# non-secret ones checked above.
lt_env_value() {
  local key="$1"
  sed -n "s/^${key}=//p" "$LT_ENV_FILE" | tail -1 | tr -d '"'"'"'' | tr -d '\r'
}

# Run a one-off command in a worker-image container on the load-test network.
# Used for migrations, seeding, reporting and isolation checks — none of which should require
# a long-running worker to exist.
lt_worker_once() {
  lt_compose run --rm --entrypoint "" endorse-worker "$@"
}

lt_mysql() {
  docker exec -i "$(lt_compose ps -q loadtest-mysql)" \
    mysql -uroot -ploadtest --default-character-set=utf8mb4 "$@" 2>/dev/null
}

lt_wait_healthy() {
  local svc="$1" tries="${2:-60}" cid
  lt_say "waiting for ${svc}"
  for _ in $(seq 1 "$tries"); do
    cid="$(lt_compose ps -q "$svc" 2>/dev/null || true)"
    if [ -n "$cid" ]; then
      case "$(docker inspect -f '{{.State.Health.Status}}' "$cid" 2>/dev/null || echo none)" in
        healthy) return 0 ;;
        none)    docker inspect -f '{{.State.Running}}' "$cid" 2>/dev/null | grep -q true && return 0 ;;
      esac
    fi
    printf '.'
    sleep 2
  done
  printf '\n'
  lt_die "${svc} did not become healthy"
}

# Set a key in .env.loadtest. The worker reads FCPATH.'.env' and PREFERS it over getenv(), so
# a docker `environment:` entry CANNOT override worker config — the file is the only lever.
lt_set_env() {
  local key="$1" value="$2"
  if grep -q "^${key}=" "$LT_ENV_FILE"; then
    # BSD sed on macOS needs the empty -i argument; | as delimiter so URLs need no escaping.
    sed -i '' "s|^${key}=.*|${key}=${value}|" "$LT_ENV_FILE" 2>/dev/null \
      || sed -i "s|^${key}=.*|${key}=${value}|" "$LT_ENV_FILE"
  else
    printf '%s=%s\n' "$key" "$value" >> "$LT_ENV_FILE"
  fi
}

lt_mock() {
  curl -s -m 10 "http://127.0.0.1:${LT_MOCK_CONTROL_PORT}$@"
}

lt_mock_profile() {
  curl -s -m 10 -X POST -H 'Content-Type: application/json' \
    --data-binary "$1" "http://127.0.0.1:${LT_MOCK_CONTROL_PORT}/_control/profile" >/dev/null
  curl -s -m 10 -X POST "http://127.0.0.1:${LT_MOCK_CONTROL_PORT}/_control/reset" >/dev/null
}
