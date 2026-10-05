#!/usr/bin/env bash
# Fetches the standalone Tailwind CSS CLI.
#
# The standalone binary, rather than the npm package, is the whole point: it means this application
# needs no Node.js, no package.json and no bundler — on a developer's machine or on the server.
# See docs/MIGRATION_PLAN.md section 4.
set -euo pipefail
cd "$(dirname "$0")/.."

VERSION="${TAILWIND_VERSION:-latest}"
case "$(uname -m)" in
  x86_64)  ARCH=x64 ;;
  aarch64|arm64) ARCH=arm64 ;;
  *) echo "Unsupported architecture: $(uname -m)" >&2; exit 1 ;;
esac

if [ "$VERSION" = "latest" ]; then
  URL="https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-linux-${ARCH}"
else
  URL="https://github.com/tailwindlabs/tailwindcss/releases/download/${VERSION}/tailwindcss-linux-${ARCH}"
fi

mkdir -p bin
curl -fsSL -o bin/tailwindcss "$URL"
chmod +x bin/tailwindcss
echo "Installed: $(./bin/tailwindcss --help 2>&1 | head -1)"
