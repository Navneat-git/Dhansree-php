#!/usr/bin/env bash
# ==============================================================================
# Script: rollback-lightsail-waf.sh
# Purpose: Cleanly rollback WAF & GeoIP modifications and restore Apache
# Target OS: Ubuntu 22.04 / 24.04 LTS
# ==============================================================================

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

log_info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
log_success() { echo -e "${GREEN}[OK]${NC} $*"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
log_err()     { echo -e "${RED}[ERROR]${NC} $*" >&2; }

if [[ $EUID -ne 0 ]]; then
   log_err "This rollback script must be run as root (or via sudo)."
   exit 1
fi

echo -e "${BOLD}${YELLOW}========================================================================${NC}"
echo -e "${BOLD}${YELLOW}       Lightsail WAF & GeoIP Security Rollback Utility                 ${NC}"
echo -e "${BOLD}${YELLOW}========================================================================${NC}"

BACKUP_BASE="/var/backups/apache2-waf-preinstall"
LATEST_TIMESTAMP_FILE="${BACKUP_BASE}_latest.txt"

# Locate backup archive
BACKUP_ARCHIVE=""
if [[ -n "${1:-}" && -f "$1" ]]; then
    BACKUP_ARCHIVE="$1"
elif [[ -f "${LATEST_TIMESTAMP_FILE}" ]]; then
    TS=$(cat "${LATEST_TIMESTAMP_FILE}")
    if [[ -f "${BACKUP_BASE}_${TS}/apache2_backup.tar.gz" ]]; then
        BACKUP_ARCHIVE="${BACKUP_BASE}_${TS}/apache2_backup.tar.gz"
    fi
fi

if [[ -z "${BACKUP_ARCHIVE}" || ! -f "${BACKUP_ARCHIVE}" ]]; then
    # Search for latest backup archive
    BACKUP_ARCHIVE=$(find /var/backups -name "apache2_backup.tar.gz" 2>/dev/null | sort -r | head -n1 || echo "")
fi

if [[ -z "${BACKUP_ARCHIVE}" || ! -f "${BACKUP_ARCHIVE}" ]]; then
    log_warn "No automated pre-install backup archive found in /var/backups/."
    log_info "Proceeding with safe procedural removal of WAF configurations..."
else
    log_info "Found pre-installation backup: ${BACKUP_ARCHIVE}"
fi

# 1. Disable Apache configurations
log_info "1. Disabling WAF & GeoIP Apache configurations..."
a2disconf lightsail-security 2>/dev/null || true
a2dismod security2 2>/dev/null || true
a2dismod maxminddb 2>/dev/null || true

# 2. Restore from backup if available
if [[ -n "${BACKUP_ARCHIVE}" && -f "${BACKUP_ARCHIVE}" ]]; then
    log_info "2. Restoring Apache configuration from ${BACKUP_ARCHIVE}..."
    tar -xzf "${BACKUP_ARCHIVE}" -C /etc
    log_success "Restored /etc/apache2 from backup."
else
    log_info "2. Removing WAF configuration directory /etc/apache2/waf..."
    rm -rf /etc/apache2/waf
    rm -f /etc/apache2/conf-available/lightsail-security.conf
fi

# 3. Remove logrotate and CLI tool
log_info "3. Removing management utilities and cron scripts..."
rm -f /usr/local/bin/waf-ctl
rm -f /etc/logrotate.d/apache2-waf

# 4. Validate Apache configuration
log_info "4. Testing Apache configuration syntax..."
if apache2ctl configtest; then
    systemctl restart apache2
    log_success "Apache configuration is clean and valid. Apache restarted."
else
    log_err "Apache configuration test failed after rollback. Please inspect 'apache2ctl configtest'."
    exit 1
fi

echo ""
echo -e "${BOLD}${GREEN}========================================================================${NC}"
echo -e "${BOLD}${GREEN}   Rollback Successful: Apache restored to original clean state.       ${NC}"
echo -e "${BOLD}${GREEN}========================================================================${NC}"
exit 0
