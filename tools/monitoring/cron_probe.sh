#!/usr/bin/env sh
set -eu

if [ "$#" -lt 3 ]; then
  echo "Usage: $0 <app> <job> <url> [curl args...]" >&2
  exit 1
fi

APP_NAME="$1"
JOB_NAME="$2"
URL="$3"
shift 3

LOG_DIR="${MONITOR_LOG_DIR:-/var/log/cron/structured}"
TIMEOUT_SECONDS="${CURL_TIMEOUT_SECONDS:-55}"
REQUEST_ID="$(date +%s)-$$"

mkdir -p "$LOG_DIR"

BODY_FILE="$(mktemp)"
ERR_FILE="$(mktemp)"
START_EPOCH="$(date +%s)"
START_TS="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"

CURL_EXIT=0
HTTP_CODE="$(
  curl \
    --silent \
    --show-error \
    --location \
    --max-time "$TIMEOUT_SECONDS" \
    --write-out "%{http_code}" \
    --output "$BODY_FILE" \
    --header "X-Request-Id: $REQUEST_ID" \
    "$@" \
    "$URL" 2>"$ERR_FILE"
)" || CURL_EXIT=$?

END_EPOCH="$(date +%s)"
DURATION_MS=$(( (END_EPOCH - START_EPOCH) * 1000 ))
BYTES_DOWNLOADED="$(wc -c < "$BODY_FILE" | tr -d ' ')"
RESPONSE_PREVIEW="$(head -c 200 "$BODY_FILE" | tr '\n' ' ' | tr '\r' ' ')"
RESPONSE_PREVIEW_JSON="$(printf '%s' "$RESPONSE_PREVIEW" | php -r 'echo json_encode(stream_get_contents(STDIN), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);')"

printf '%s\n' \
  "{\"ts\":\"$START_TS\",\"app\":\"$APP_NAME\",\"job\":\"$JOB_NAME\",\"request_id\":\"$REQUEST_ID\",\"url\":\"$URL\",\"duration_ms\":$DURATION_MS,\"http_status\":$HTTP_CODE,\"curl_exit_code\":$CURL_EXIT,\"bytes_downloaded\":$BYTES_DOWNLOADED,\"timed_out\":$([ "$HTTP_CODE" = "000" ] && printf true || printf false),\"response_preview\":$RESPONSE_PREVIEW_JSON}" \
  >> "$LOG_DIR/$APP_NAME.jsonl"

cat "$BODY_FILE"

rm -f "$BODY_FILE" "$ERR_FILE"
