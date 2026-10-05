#!/usr/bin/env bash
# Regenerates tests/Fixtures/node-parity.json from the Node application's compiled modules.
#
# Run this whenever the Node side changes how it hashes passwords, seals secrets, computes TOTP
# codes or signs Razorpay payloads. If the PHPUnit parity tests then fail, the PHP port must be
# corrected — never the fixture.
set -euo pipefail
cd "$(dirname "$0")/.."

NODE_APP_ROOT="${NODE_APP_ROOT:-$(cd .. && pwd)/Dhansree}"
export NODE_APP_ROOT

if [ ! -d "$NODE_APP_ROOT/apps/api/dist" ]; then
  echo "The Node application is not built: $NODE_APP_ROOT/apps/api/dist is missing." >&2
  echo "Run 'npm run build' in $NODE_APP_ROOT first, or set NODE_APP_ROOT." >&2
  exit 1
fi

node scripts/generate-parity-fixtures.cjs > tests/Fixtures/node-parity.json
echo "Regenerated tests/Fixtures/node-parity.json from $NODE_APP_ROOT"
