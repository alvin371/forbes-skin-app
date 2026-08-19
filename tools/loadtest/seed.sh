#!/usr/bin/env bash
# Seed the disposable load-test database.
#
#   tools/loadtest/seed.sh [--count 13500] [--urls tests/fixtures/loadtest_urls.txt] [--keep]
#
# --keep appends to the existing dataset instead of truncating first.

# shellcheck source=lib.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

COUNT=13500
URLS=""
RESET=1
PATHOLOGICAL=1

while [ $# -gt 0 ]; do
  case "$1" in
    --count) COUNT="${2:?--count needs a number}"; shift 2 ;;
    --urls)  URLS="${2:?--urls needs a path}"; shift 2 ;;
    --keep)  RESET=0; shift ;;
    --no-pathological) PATHOLOGICAL=0; shift ;;
    *) lt_die "unknown argument: $1" ;;
  esac
done

lt_require_env_file

ARGS=(--count "$COUNT" --reset "$RESET" --pathological "$PATHOLOGICAL")
if [ -n "$URLS" ]; then
  [ -f "${LT_ROOT}/${URLS}" ] || lt_die "URL corpus not found: ${URLS}"
  ARGS+=(--urls "$URLS")
fi

lt_say "seeding ${COUNT} valid rows"
lt_worker_once php tools/loadtest/seed.php "${ARGS[@]}"
