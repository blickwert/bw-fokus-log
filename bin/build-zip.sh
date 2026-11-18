#!/usr/bin/env bash
# Baut die installierbare Plugin-ZIP (ohne README.md, bin/, .git).
set -euo pipefail

cd "$(dirname "$0")/.."
VERSION=$(grep -m1 "Version:" fokus-log.php | awk '{print $NF}')
OUT="fokus-log-${VERSION}.zip"
TMP=$(mktemp -d)

mkdir -p "$TMP/fokus-log"
cp -r fokus-log.php uninstall.php readme.txt includes assets "$TMP/fokus-log/"
rm -f "$OUT"
(cd "$TMP" && zip -rq "$OLDPWD/$OUT" fokus-log)
rm -rf "$TMP"

echo "Fertig: $OUT"
