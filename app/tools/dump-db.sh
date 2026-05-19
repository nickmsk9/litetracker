#!/usr/bin/env bash

set -euo pipefail

REPO_ROOT="$(git rev-parse --show-toplevel)"
OUTPUT_DIR="$REPO_ROOT/database"
OUTPUT_FILE="$OUTPUT_DIR/litetracker.sql"
TMP_FILE="$(mktemp)"

cleanup() {
  rm -f "$TMP_FILE"
}

trap cleanup EXIT

mkdir -p "$OUTPUT_DIR"

if ! docker compose ps --status running db >/dev/null 2>&1; then
  echo "Database container 'db' is not running." >&2
  echo "Start it with 'docker compose up -d db' and try again." >&2
  exit 1
fi

docker compose exec -T db sh -lc '
  MYSQL_PWD="" exec mysqldump \
    -uroot \
    --default-character-set=utf8mb4 \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --no-tablespaces \
    --set-gtid-purged=OFF \
    --skip-comments \
    --skip-dump-date \
    --databases "${MYSQL_DATABASE:-lite}"
' >"$TMP_FILE"

if [ -f "$OUTPUT_FILE" ] && cmp -s "$TMP_FILE" "$OUTPUT_FILE"; then
  echo "SQL dump is already up to date: $OUTPUT_FILE"
  exit 0
fi

mv "$TMP_FILE" "$OUTPUT_FILE"
git -C "$REPO_ROOT" add "$OUTPUT_FILE"
echo "Updated SQL dump: $OUTPUT_FILE"
