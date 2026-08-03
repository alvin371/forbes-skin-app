#!/usr/bin/env bash
# Docker-backed endorse-refresh integration runner.
# Starts a disposable MySQL, runs the integration suite (which applies the real
# migrations), and tears the container down. In CI, FORBES_REQUIRE_DB=1 makes the
# suite FAIL (never silently skip) if the DB is unavailable.
set -euo pipefail

CONTAINER="${FORBES_TEST_MYSQL_CONTAINER:-forbes_it_mysql}"
PORT="${FORBES_TEST_MYSQL_PORT:-33061}"
IMAGE="${FORBES_TEST_MYSQL_IMAGE:-mysql:8.0}"

cleanup() { docker rm -f "$CONTAINER" >/dev/null 2>&1 || true; }
trap cleanup EXIT

cleanup
echo "==> starting $IMAGE on :$PORT"
docker run -d --name "$CONTAINER" \
  -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=forbes_test \
  -p "${PORT}:3306" "$IMAGE" >/dev/null

echo -n "==> waiting for mysql"
for i in $(seq 1 60); do
  if docker exec "$CONTAINER" mysqladmin ping -proot >/dev/null 2>&1; then echo " up (${i}s)"; break; fi
  echo -n "."; sleep 1
  if [ "$i" -eq 60 ]; then echo " FAILED"; exit 1; fi
done

export FORBES_TEST_DB="host=127.0.0.1;port=${PORT};user=root;pass=root;db=forbes_test"
export FORBES_REQUIRE_DB=1   # integration job must not silently skip

echo "==> unit suite"
php vendor/bin/phpunit --configuration phpunit.xml --colors=always | tail -3
echo "==> integration + e2e suite (real MySQL, mandatory)"
php vendor/bin/phpunit -c phpunit-integration.xml --colors=always --testdox
