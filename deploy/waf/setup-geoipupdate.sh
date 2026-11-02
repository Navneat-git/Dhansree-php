#!/usr/bin/env bash
# ==============================================================================
# Script: setup-geoipupdate.sh
# Purpose: Securely configure automated MaxMind GeoLite2 database updates
# ==============================================================================

set -euo pipefail

if [[ $EUID -ne 0 ]]; then
   echo "This script must be run as root (or via sudo)." >&2
   exit 1
fi

GEOIP_CONF="/etc/GeoIP.conf"

echo "=== MaxMind GeoLite2 Automated Update Setup ==="
echo "If you don't have a free MaxMind account yet, sign up at:"
echo "https://www.maxmind.com/en/geolite2/signup"
echo ""

ACCOUNT_ID="${1:-}"
LICENSE_KEY="${2:-}"

if [[ -z "${ACCOUNT_ID}" ]]; then
    read -r -p "Enter your MaxMind Account ID: " ACCOUNT_ID
fi

if [[ -z "${LICENSE_KEY}" ]]; then
    read -r -s -p "Enter your MaxMind License Key: " LICENSE_KEY
    echo ""
fi

if [[ -z "${ACCOUNT_ID}" || -z "${LICENSE_KEY}" ]]; then
    echo "[ERROR] Both Account ID and License Key are required." >&2
    exit 1
fi

cat > "${GEOIP_CONF}" <<EOF
# GeoIP.conf generated for automated Lightsail WAF updates
AccountID ${ACCOUNT_ID}
LicenseKey ${LICENSE_KEY}
EditionIDs GeoLite2-Country
DatabaseDirectory /var/lib/GeoIP
EOF

# Restrict permissions so only root can view the license key
chmod 600 "${GEOIP_CONF}"
chown root:root "${GEOIP_CONF}"

echo "[OK] /etc/GeoIP.conf updated with secure 0600 permissions."
echo "Running initial test download with geoipupdate..."

mkdir -p /var/lib/GeoIP
if geoipupdate -v; then
    chmod 644 /var/lib/GeoIP/GeoLite2-Country.mmdb
    chown www-data:www-data /var/lib/GeoIP/GeoLite2-Country.mmdb
    echo "[OK] Successfully downloaded latest GeoLite2-Country database!"
else
    echo "[WARN] geoipupdate encountered an issue. Please verify your Account ID and License Key."
fi

# Enable systemd timer if available, otherwise add cron job
if systemctl list-unit-files | grep -q "geoipupdate.timer"; then
    systemctl enable --now geoipupdate.timer
    echo "[OK] Enabled systemd geoipupdate.timer for weekly automatic updates."
else
    # Fallback weekly cron job
    cat > /etc/cron.weekly/geoipupdate <<'EOFCRON'
#!/bin/sh
/usr/bin/geoipupdate && systemctl reload apache2 >/dev/null 2>&1
EOFCRON
    chmod 755 /etc/cron.weekly/geoipupdate
    echo "[OK] Created weekly cron job at /etc/cron.weekly/geoipupdate"
fi

echo "=== GeoLite2 Setup Complete ==="
