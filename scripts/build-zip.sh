#!/usr/bin/env bash
# Builds the installable plugin ZIP: compiled app, production-only Composer
# autoloader, no dev tooling, no sources of the app.
set -euo pipefail

cd "$(dirname "$0")/.."
ROOT=$PWD
VERSION=$(grep -m1 "Version:" free/bw-fokus-log.php | awk '{print $NF}')
OUT="$ROOT/bw-fokus-log-${VERSION}.zip"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

npm ci --no-audit --no-fund
npm run build
python3 scripts/i18n.py --strict

# Work in a copy so the dev vendor folder stays untouched.
mkdir -p "$TMP/src"
cp -r free "$TMP/src/free"
rm -rf "$TMP/src/free/vendor"
(cd "$TMP/src/free" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress)

mkdir -p "$TMP/zip"
cp -r "$TMP/src/free" "$TMP/zip/bw-fokus-log"
rm -f "$OUT"
(cd "$TMP/zip" && zip -rq "$OUT" bw-fokus-log)

echo "Done: $OUT"
