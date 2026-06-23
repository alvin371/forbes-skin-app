#!/usr/bin/env bash
# Convenience runner — loads .env, runs a k6 scenario with caffeinate + JSON output.
# usage: bash loadtest/run.sh [smoke|baseline|soak|stress|spike]   (default smoke)
set -euo pipefail
cd "$(dirname "$0")"
[ -f ./.env ] || { echo "missing loadtest/.env"; exit 1; }
set -a; source ./.env; set +a
SC="${1:-smoke}"
mkdir -p results
OUT="results/${SC}-$(date +%Y%m%d-%H%M%S).json"
echo "scenario=$SC  base=$BASE_URL  out=$OUT"
echo "ALWAYS run smoke first; watch Dozzle (prod). Ctrl-C to abort."
exec caffeinate -i k6 run --out "json=$OUT" -e "SCENARIO=$SC" k6/forbes-loadtest.js
