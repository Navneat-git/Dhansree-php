#!/usr/bin/env bash
# Compiles Tailwind CSS. No Node, no bundler.
set -euo pipefail
cd "$(dirname "$0")/.."
[ -x bin/tailwindcss ] || { echo "Run scripts/install-tailwind.sh first." >&2; exit 1; }
exec ./bin/tailwindcss -i resources/css/app.css -o public/css/app.css "$@"
