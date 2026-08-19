#!/usr/bin/env bash
# Destroy the disposable load-test stack.
#
# Scoped to the forbes-loadtest compose project and nothing else, so this cannot reach the
# dev stack in docker-compose.yml, let alone anything deployed. Reports are kept unless
# --purge-reports is passed — losing evidence to a teardown is worse than leaving files.
#
#   tools/loadtest/down.sh [--purge-reports] [--purge-certs]

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

PURGE_REPORTS=0
PURGE_CERTS=0
while [ $# -gt 0 ]; do
  case "$1" in
    --purge-reports) PURGE_REPORTS=1; shift ;;
    --purge-certs)   PURGE_CERTS=1; shift ;;
    *) lt_die "unknown argument: $1" ;;
  esac
done

lt_say "removing containers, networks and volumes for project ${LT_PROJECT}"
lt_compose down --volumes --remove-orphans

if [ "$PURGE_REPORTS" -eq 1 ]; then
  lt_say "purging reports"
  rm -rf "${LT_REPORTS:?}"/*
else
  lt_say "reports kept in tools/loadtest/reports (pass --purge-reports to delete)"
fi

if [ "$PURGE_CERTS" -eq 1 ]; then
  lt_say "purging mock TLS material"
  rm -rf "${LT_CERTS:?}"
fi

lt_say "down"
