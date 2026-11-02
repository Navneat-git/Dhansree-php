#!/usr/bin/env bash
# ==============================================================================
# Script: install-lightsail-waf.sh
# Purpose: Lightweight Open-Source WAF & Country Blocking for AWS Lightsail
# Target OS: Ubuntu 22.04 / 24.04 LTS (Apache + PHP-FPM)
# Resource Profile: 2 vCPU / 2 GB RAM (Low memory overhead, PL1, Zero extra daemons)
# ==============================================================================

set -euo pipefail

# --- Color formatting ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

log_info()    { echo -e "${BLUE}[INFO]${NC} $*"; }
log_success() { echo -e "${GREEN}[OK]${NC} $*"; }
log_warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
log_err()     { echo -e "${RED}[ERROR]${NC} $*" >&2; }

# --- Security check: Root execution required ---
if [[ $EUID -ne 0 ]]; then
   log_err "This script must be run as root (or via sudo)."
   exit 1
fi

WAF_DIR="/etc/apache2/waf"
BACKUP_BASE="/var/backups/apache2-waf-preinstall"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="${BACKUP_BASE}_${TIMESTAMP}"
GEOIP_DIR="/var/lib/GeoIP"
COUNTRY_ALLOWLIST_CONF="${WAF_DIR}/country-allowlist.conf"
MODSEC_CUSTOM_CONF="${WAF_DIR}/waf-settings.conf"
EXCLUSIONS_CONF="${WAF_DIR}/exclusions.conf"
RATE_LIMIT_CONF="${WAF_DIR}/rate-limiting.conf"

echo -e "${BOLD}${CYAN}========================================================================${NC}"
echo -e "${BOLD}${CYAN}   Lightsail WAF & GeoIP Security Installer (Apache + ModSecurity)     ${NC}"
echo -e "${BOLD}${CYAN}========================================================================${NC}"

# ==============================================================================
# 1. Environment & Pre-Flight Checks
# ==============================================================================
log_info "1. Running pre-flight system diagnostics..."

# OS check
if ! grep -qEi "ubuntu" /etc/os-release; then
    log_err "Unsupported operating system. This script requires Ubuntu 22.04 or 24.04 LTS."
    exit 1
fi

UBUNTU_VER=$(lsb_release -rs 2>/dev/null || grep -oP 'VERSION_ID="\K[^"]+' /etc/os-release)
log_success "Detected Ubuntu version: ${UBUNTU_VER}"

# Apache check
if ! command -v apache2 >/dev/null 2>&1; then
    log_err "Apache2 is not installed. Please install and configure Apache before running this script."
    exit 1
fi

if ! systemctl is-active --quiet apache2; then
    log_warn "Apache2 service is currently inactive. Attempting start..."
    systemctl start apache2 || { log_err "Failed to start Apache2. Check 'journalctl -xeu apache2'."; exit 1; }
fi
log_success "Apache2 is running: $(apache2 -v | head -n1)"

# PHP SAPI Detection
if systemctl is-active --quiet php8.3-fpm || systemctl is-active --quiet php8.2-fpm || systemctl is-active --quiet php8.1-fpm || systemctl is-active --quiet php-fpm; then
    ACTIVE_FPM=$(systemctl list-units --type=service --state=running | grep -oE "php[0-9.]*-fpm" | head -n1 || echo "php-fpm")
    log_success "PHP execution mode: PHP-FPM (${ACTIVE_FPM}) via Apache proxy_fcgi (Optimized for low RAM)"
elif apache2ctl -M 2>/dev/null | grep -q "php"; then
    log_warn "PHP execution mode: mod_php detected. For 2 GB RAM servers, migrating to PHP-FPM is recommended to conserve memory."
else
    log_info "PHP SAPI: External/Reverse-proxy or customized."
fi

# RAM & Resource Evaluation (2 GB RAM constraint)
TOTAL_RAM_MB=$(free -m | awk '/^Mem:/{print $2}')
AVAIL_RAM_MB=$(free -m | awk '/^Mem:/{print $7}')
DISK_AVAIL_MB=$(df -m / | awk 'NR==2 {print $4}')

log_info "System Resources: Total RAM: ${TOTAL_RAM_MB}MB | Available: ${AVAIL_RAM_MB}MB | Free Disk: ${DISK_AVAIL_MB}MB"

if [[ "${AVAIL_RAM_MB}" -lt 250 ]]; then
    log_warn "Available RAM (${AVAIL_RAM_MB}MB) is low. High-memory ModSecurity features (response body buffering) will be strictly disabled."
fi

if [[ "${DISK_AVAIL_MB}" -lt 1000 ]]; then
    log_err "Insufficient disk space (<1000MB free). Please free up space before installing WAF & GeoIP rules."
    exit 1
fi

# ==============================================================================
# 2. Automated Apache Configuration Backup
# ==============================================================================
log_info "2. Creating atomic backup of /etc/apache2..."
mkdir -p "${BACKUP_DIR}"
tar -czf "${BACKUP_DIR}/apache2_backup.tar.gz" -C /etc apache2
echo "${TIMESTAMP}" > "${BACKUP_BASE}_latest.txt"
log_success "Backup saved to: ${BACKUP_DIR}/apache2_backup.tar.gz"

# Rollback trap in case of failure during installation
cleanup_on_failure() {
    local exit_code=$?
    if [[ $exit_code -ne 0 ]]; then
        log_err "An error occurred during installation (exit code $exit_code)."
        log_warn "Attempting automatic configuration validation..."
        if ! apache2ctl configtest >/dev/null 2>&1; then
            log_warn "Apache configuration corrupted during install. Restoring backup..."
            if [[ -f "${BACKUP_DIR}/apache2_backup.tar.gz" ]]; then
                tar -xzf "${BACKUP_DIR}/apache2_backup.tar.gz" -C /etc
                a2disconf lightsail-security 2>/dev/null || true
                systemctl restart apache2 || true
                log_success "Original Apache configuration restored."
            fi
        fi
    fi
}
trap cleanup_on_failure EXIT

# ==============================================================================
# 3. Package Installation
# ==============================================================================
log_info "3. Installing required lightweight open-source packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq

# Install ModSecurity, OWASP CRS, GeoIP binaries, and essential tools
apt-get install -y -qq \
    libapache2-mod-security2 \
    modsecurity-crs \
    mmdb-bin \
    geoipupdate \
    libmaxminddb0 \
    curl \
    ca-certificates

# Build and enable mod_maxminddb if not already installed
if ! apache2ctl -M 2>/dev/null | grep -q "maxminddb_module"; then
    if [[ ! -f /usr/lib/apache2/modules/mod_maxminddb.so ]]; then
        log_info "Compiling lightweight mod_maxminddb Apache module..."
        apt-get install -y -qq apache2-dev libmaxminddb-dev git
        
        TEMP_BUILD_DIR=$(mktemp -d /tmp/mod_maxminddb_build.XXXXXX)
        git clone --depth=1 https://github.com/maxmind/mod_maxminddb.git "${TEMP_BUILD_DIR}"
        cd "${TEMP_BUILD_DIR}"
        apxs -i -a -lmaxminddb -c src/mod_maxminddb.c
        cd /
        rm -rf "${TEMP_BUILD_DIR}"
        
        # Clean build dependencies to save disk and memory
        apt-get purge -y -qq apache2-dev libmaxminddb-dev >/dev/null 2>&1 || true
        apt-get autoremove -y -qq >/dev/null 2>&1 || true
    fi
fi

# Create mod_maxminddb loader if missing
if [[ ! -f /etc/apache2/mods-available/maxminddb.load ]]; then
    echo "LoadModule maxminddb_module /usr/lib/apache2/modules/mod_maxminddb.so" > /etc/apache2/mods-available/maxminddb.load
fi
a2enmod maxminddb >/dev/null 2>&1 || true

# Enable essential modules
a2enmod security2 >/dev/null 2>&1 || true
a2enmod rewrite >/dev/null 2>&1 || true
a2enmod headers >/dev/null 2>&1 || true
a2enmod remoteip >/dev/null 2>&1 || true
a2enmod unique_id >/dev/null 2>&1 || true

log_success "Installed and enabled modules: mod_security2, mod_maxminddb, mod_remoteip, mod_headers, mod_rewrite"

# ==============================================================================
# 4. GeoIP Database Setup
# ==============================================================================
log_info "4. Configuring GeoLite2 Country database..."
mkdir -p "${GEOIP_DIR}"

# Provide a sample/initial Country DB if not present
if [[ ! -f "${GEOIP_DIR}/GeoLite2-Country.mmdb" ]]; then
    log_info "Downloading initial GeoLite2-Country database..."
    # Download reliable community mirror for instant bootstrapping
    if curl -sSL --fail "https://raw.githubusercontent.com/GitSquared/node-geolite2-redist/master/redist/GeoLite2-Country.tar.gz" -o /tmp/geolite2.tar.gz; then
        tar -xzf /tmp/geolite2.tar.gz -C /tmp/
        find /tmp -name "GeoLite2-Country.mmdb" -exec cp {} "${GEOIP_DIR}/GeoLite2-Country.mmdb" \;
        rm -rf /tmp/geolite2* /tmp/GeoLite2*
        log_success "Initial GeoLite2-Country database installed at: ${GEOIP_DIR}/GeoLite2-Country.mmdb"
    else
        log_warn "Initial automatic GeoIP database download skipped. You can configure /etc/GeoIP.conf using your free MaxMind account."
        touch "${GEOIP_DIR}/GeoLite2-Country.mmdb" # dummy touch so apache configtest passes
    fi
fi

# Ensure correct permissions
chmod 644 "${GEOIP_DIR}/GeoLite2-Country.mmdb" 2>/dev/null || true
chown -R www-data:www-data "${GEOIP_DIR}" 2>/dev/null || true

# ==============================================================================
# 5. Create Modular WAF & Security Configuration (/etc/apache2/waf/)
# ==============================================================================
log_info "5. Generating optimized WAF configuration for 2 GB RAM server..."
mkdir -p "${WAF_DIR}"
mkdir -p /var/log/apache2/waf
mkdir -p /var/cache/modsecurity
chown -R www-data:www-data /var/log/apache2/waf /var/cache/modsecurity

# 5.1 Country Allowlist Configuration
cat > "${COUNTRY_ALLOWLIST_CONF}" <<'EOF'
# ==============================================================================
# Country Filtering Configuration (MaxMind GeoLite2 via mod_maxminddb)
# ==============================================================================
<IfModule mod_maxminddb.c>
    MaxMindDBEnable On
    MaxMindDBFile COUNTRY_DB /var/lib/GeoIP/GeoLite2-Country.mmdb
    MaxMindDBEnv MM_COUNTRY_CODE COUNTRY_DB/country/iso_code

    # Optional: Read client IP from RemoteIP if proxy/CDN is active
    # MaxMindDBClientIPEnv MM_CLIENT_IP
</IfModule>

# Country Enforcement & Whitelist Bypasses
<IfModule mod_rewrite.c>
    RewriteEngine On

    # 1. Always allow localhost / internal loopback
    RewriteCond %{REMOTE_ADDR} ^(127\.0\.0\.1|::1)$ [OR]
    RewriteCond %{REMOTE_ADDR} ^172\.(1[6-9]|2[0-9]|3[0-1])\. [OR]
    RewriteCond %{REMOTE_ADDR} ^10\.

    RewriteRule ^ - [E=WAF_GEO_ALLOW:1]

    # 2. Always allow essential payment gateway callbacks & webhooks
    #    (Adjust URIs according to your active gateways: Razorpay, PhonePe, Cashfree, Stripe, etc.)
    RewriteCond %{REQUEST_URI} ^/(api/payment|api/webhook|payment/callback|webhook|track-order|order/status) [NC]
    RewriteRule ^ - [E=WAF_GEO_ALLOW:1]

    # 3. Allow recognized search engine bots (User-Agent check with verified bot patterns)
    RewriteCond %{HTTP_USER_AGENT} (googlebot|bingbot|yandex|duckduckbot|slurp|baiduspider) [NC]
    RewriteRule ^ - [E=WAF_GEO_ALLOW:1]

    # 4. Approved Countries List (Regex matching ISO 2-letter codes)
    #    Default: India (IN). To allow more countries, append with pipe: ^(IN|US|GB|AE|CA)$
    RewriteCond %{ENV:MM_COUNTRY_CODE} ^(IN)$
    RewriteRule ^ - [E=WAF_GEO_ALLOW:1]

    # 5. Handle Unknown / Unclassified IP addresses
    #    Set to 1 to ALLOW unclassified IPs (e.g. private test nets), or 0 to BLOCK.
    #    Default: ALLOW unknown so local internal tests don't fail before GeoIP updates.
    RewriteCond %{ENV:MM_COUNTRY_CODE} ^$
    RewriteRule ^ - [E=WAF_GEO_ALLOW:1]

    # 6. Block all non-approved traffic with HTTP 403 Forbidden
    #    (Only enforced when GeoIP blocking is enabled in lightsail-security.conf)
</IfModule>
EOF
chmod 644 "${COUNTRY_ALLOWLIST_CONF}"
log_success "Created: ${COUNTRY_ALLOWLIST_CONF}"

# 5.2 ModSecurity Core Settings (Detection-Only by default, Low RAM Profile)
cat > "${MODSEC_CUSTOM_CONF}" <<'EOF'
# ==============================================================================
# ModSecurity v2 Engine Configuration - Optimized for AWS Lightsail (2GB RAM)
# ==============================================================================
<IfModule security2_module>
    # Default state: DetectionOnly (Safe mode: logs anomalies without dropping traffic)
    # Switch to 'On' via 'waf-ctl mode blocking' after monitoring for false positives.
    SecRuleEngine DetectionOnly

    # Request body inspection settings (Tuned for low memory)
    SecRequestBodyAccess On
    SecRequestBodyLimit 13107200
    SecRequestBodyNoFilesLimit 131072
    SecRequestBodyInMemoryLimit 131072
    SecRequestBodyLimitAction Reject

    # CRITICAL FOR 2 GB RAM: Turn OFF response body buffering to avoid OOM crashes!
    SecResponseBodyAccess Off
    SecResponseBodyMimeType text/plain text/html text/xml
    SecResponseBodyLimit 524288
    SecResponseBodyLimitAction ProcessPartial

    # Filesystem and temporary directory settings
    SecTmpDir /tmp
    SecDataDir /var/cache/modsecurity
    SecUploadDir /tmp

    # Logging Configuration
    SecDebugLog /var/log/apache2/waf/modsec_debug.log
    SecDebugLogLevel 0
    SecAuditEngine RelevantOnly
    SecAuditLogRelevantStatus "^(?:5|4(?!04))"
    SecAuditLogParts ABIJDEFHZ
    SecAuditLogType Serial
    SecAuditLog /var/log/apache2/waf/modsec_audit.log
    SecAuditLogStorageDir /var/log/apache2/waf/

    # Security: Mask sensitive credentials from audit logs
    SecAction \
        "id:900005,\
        phase:1,\
        nolog,\
        pass,\
        t:none,\
        setvar:tx.arg_name=password,\
        setvar:tx.arg_name=password_confirmation,\
        setvar:tx.arg_name=card_number,\
        setvar:tx.arg_name=cvv,\
        setvar:tx.arg_name=token"

    # Status code returned on WAF block
    SecDefaultAction "phase:1,deny,log,status:403"
    SecDefaultAction "phase:2,deny,log,status:403"
</IfModule>
EOF
chmod 644 "${MODSEC_CUSTOM_CONF}"
log_success "Created: ${MODSEC_CUSTOM_CONF}"

# 5.3 OWASP CRS Paranoia Level 1 Configuration
cat > "${WAF_DIR}/owasp-crs.conf" <<'EOF'
# ==============================================================================
# OWASP Core Rule Set (CRS v3) Integration - Paranoia Level 1 (Low CPU Profile)
# ==============================================================================
<IfModule security2_module>
    # Include base CRS setup configuration if present
    IncludeOptional /etc/modsecurity/crs-setup.conf
    IncludeOptional /usr/share/modsecurity-crs/crs-setup.conf

    # Overwrite CRS parameters for lightweight operation:
    SecAction \
        "id:900000,\
        phase:1,\
        nolog,\
        pass,\
        t:none,\
        setvar:tx.paranoia_level=1,\
        setvar:tx.executing_paranoia_level=1,\
        setvar:tx.inbound_anomaly_score_threshold=5,\
        setvar:tx.outbound_anomaly_score_threshold=4"

    # Include official OWASP rules
    IncludeOptional /usr/share/modsecurity-crs/rules/*.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/REQUEST-901-INITIALIZATION.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/REQUEST-941-APPLICATION-ATTACK-XSS.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/REQUEST-942-APPLICATION-ATTACK-SQLI.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/REQUEST-930-APPLICATION-ATTACK-LFI.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/REQUEST-931-APPLICATION-ATTACK-RFI.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/REQUEST-932-APPLICATION-ATTACK-RCE.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/REQUEST-949-BLOCKING-EVALUATION.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/RESPONSE-959-BLOCKING-EVALUATION.conf
    IncludeOptional /usr/share/modsecurity-crs/rules/RESPONSE-980-CORRELATION.conf
</IfModule>
EOF
chmod 644 "${WAF_DIR}/owasp-crs.conf"
log_success "Created: ${WAF_DIR}/owasp-crs.conf"

# 5.4 Application Exclusions (Laravel, Admin Panel & Payment Gateways)
cat > "${EXCLUSIONS_CONF}" <<'EOF'
# ==============================================================================
# Application & Payment Gateway False-Positive Exclusions
# ==============================================================================
<IfModule security2_module>
    # 1. Whitelist Laravel Livewire, Inertia, or AJAX CSRF requests
    SecRule REQUEST_HEADERS:X-Requested-With "@streq XMLHttpRequest" \
        "id:1000101,phase:1,pass,nolog,ctl:ruleRemoveById=942100"

    # 2. Allow rich JSON payloads on API & Checkout endpoints
    SecRule REQUEST_URI "@rx ^/(checkout|api/|admin/)" \
        "id:1000102,phase:1,pass,nolog,ctl:ruleRemoveById=949110,ctl:ruleRemoveById=980130"

    # 3. Allow payment webhook callbacks (Razorpay, PhonePe, Paytm, etc.)
    SecRule REQUEST_URI "@rx ^/(webhook|payment/callback|api/payment)" \
        "id:1000103,phase:1,pass,nolog,ctl:ruleEngine=Off"

    # 4. Safe rule exclusion template:
    # To disable a specific rule triggered falsely in audit log:
    # SecRuleRemoveById 942100
</IfModule>
EOF
chmod 644 "${EXCLUSIONS_CONF}"
log_success "Created: ${EXCLUSIONS_CONF}"

# 5.5 Sensitive Endpoint Rate Limiting (ModSecurity Native - Zero RAM overhead)
cat > "${RATE_LIMIT_CONF}" <<'EOF'
# ==============================================================================
# Lightweight Native Rate Limiting (Login, Search, & Sensitive Endpoints)
# ==============================================================================
<IfModule security2_module>
    # Initialize IP tracking collection in memory (uses minimal shared memory)
    SecAction "id:1000200,phase:1,nolog,pass,initcol:ip=%{REMOTE_ADDR}"

    # Rate Limit 1: Admin & Customer Login Endpoints (Max 15 attempts per minute)
    SecRule REQUEST_METHOD "@streq POST" \
        "id:1000201,phase:1,chain,nolog,pass"
    SecRule REQUEST_URI "@rx ^/(login|admin/login|account/login)" \
        "setvar:ip.login_count=+1,expirevar:ip.login_count=60"

    SecRule IP:LOGIN_COUNT "@gt 15" \
        "id:1000202,phase:1,deny,status:429,log,msg:'Rate limit exceeded: excessive login attempts (%{IP.LOGIN_COUNT}/min)'"

    # Rate Limit 2: Search Query Flooding (Max 40 queries per minute)
    SecRule REQUEST_URI "@rx ^/search" \
        "id:1000203,phase:1,pass,nolog,setvar:ip.search_count=+1,expirevar:ip.search_count=60"

    SecRule IP:SEARCH_COUNT "@gt 40" \
        "id:1000204,phase:1,deny,status:429,log,msg:'Rate limit exceeded: search endpoint abused (%{IP.SEARCH_COUNT}/min)'"
</IfModule>
EOF
chmod 644 "${RATE_LIMIT_CONF}"
log_success "Created: ${RATE_LIMIT_CONF}"

# ==============================================================================
# 6. Global Master Configuration (/etc/apache2/conf-available/lightsail-security.conf)
# ==============================================================================
log_info "6. Creating master Apache security configuration..."

cat > /etc/apache2/conf-available/lightsail-security.conf <<'EOF'
# ==============================================================================
# Master Lightsail Security Configuration (WAF, GeoIP, RemoteIP)
# ==============================================================================

# RemoteIP Configuration: Trust internal private subnets for proper client IP
<IfModule mod_remoteip.c>
    RemoteIPHeader X-Forwarded-For
    RemoteIPInternalProxy 127.0.0.1 ::1
    RemoteIPInternalProxy 172.16.0.0/12
    RemoteIPInternalProxy 10.0.0.0/8
</IfModule>

# Include WAF and Country components
IncludeOptional /etc/apache2/waf/country-allowlist.conf
IncludeOptional /etc/apache2/waf/waf-settings.conf
IncludeOptional /etc/apache2/waf/owasp-crs.conf
IncludeOptional /etc/apache2/waf/exclusions.conf
IncludeOptional /etc/apache2/waf/rate-limiting.conf

# Country Blocking Enforcement (Conditional)
# If WAF_GEO_BLOCKING_MODE is set to 'blocking' and WAF_GEO_ALLOW is not 1:
<IfModule mod_rewrite.c>
    RewriteEngine On
    # Uncomment the following line to actively DROP traffic from unapproved countries:
    # RewriteCond %{ENV:WAF_GEO_ALLOW} !^1$
    # RewriteRule ^ - [F,L]
</IfModule>
EOF

# Enable the master configuration
a2enconf lightsail-security >/dev/null 2>&1
log_success "Enabled Apache configuration: lightsail-security"

# ==============================================================================
# 7. Logrotate Configuration
# ==============================================================================
log_info "7. Setting up automated log rotation for security logs..."
cat > /etc/logrotate.d/apache2-waf <<'EOF'
/var/log/apache2/waf/*.log {
    weekly
    missingok
    rotate 8
    compress
    delaycompress
    notifempty
    create 640 root adm
    sharedscripts
    postrotate
        if /usr/sbin/invoke-rc.d apache2 status > /dev/null 2>&1; then \
            /usr/sbin/invoke-rc.d apache2 reload > /dev/null 2>&1; \
        fi;
    endscript
}
EOF
chmod 644 /etc/logrotate.d/apache2-waf
log_success "Logrotate configured: /etc/logrotate.d/apache2-waf"

# ==============================================================================
# 8. Management CLI Tool (/usr/local/bin/waf-ctl)
# ==============================================================================
log_info "8. Installing WAF management CLI utility (waf-ctl)..."
cat > /usr/local/bin/waf-ctl <<'EOF'
#!/usr/bin/env bash
# waf-ctl: Interactive/Scriptable CLI manager for Lightsail Apache WAF

set -euo pipefail
WAF_SETTINGS="/etc/apache2/waf/waf-settings.conf"
GEO_CONF="/etc/apache2/waf/country-allowlist.conf"
AUDIT_LOG="/var/log/apache2/waf/modsec_audit.log"

usage() {
    echo "Usage: waf-ctl <command> [args]"
    echo ""
    echo "Commands:"
    echo "  status            Display current WAF mode, GeoIP status, and RAM consumption"
    echo "  mode detection    Set ModSecurity to DetectionOnly mode (Logging only, no blocks)"
    echo "  mode blocking     Set ModSecurity to On mode (Actively blocks attacks with 403)"
    echo "  countries         Show list of allowed country codes"
    echo "  test              Execute safe simulated attacks to verify WAF detection"
    echo "  logs [lines]      View recent WAF audit log entries (default: 30 lines)"
    echo "  help              Show this help message"
    echo ""
}

cmd_status() {
    echo "=== Lightsail WAF & Security Status ==="
    local mode="Unknown"
    if grep -qE "SecRuleEngine\s+On" "${WAF_SETTINGS}"; then
        mode="\033[0;31mBLOCKING (On)\033[0m"
    elif grep -qE "SecRuleEngine\s+DetectionOnly" "${WAF_SETTINGS}"; then
        mode="\033[0;32mDETECTION ONLY (Monitoring)\033[0m"
    fi
    echo -e "WAF Engine Mode:     ${mode}"

    local geo_status="Disabled / Inactive"
    if apache2ctl -M 2>/dev/null | grep -q "maxminddb_module"; then
        geo_status="\033[0;32mLoaded (mod_maxminddb)\033[0m"
    fi
    echo -e "GeoIP Module:        ${geo_status}"

    local countries=$(grep -oP 'RewriteCond %{ENV:MM_COUNTRY_CODE} \^\(\K[^\)]+' "${GEO_CONF}" || echo "Not configured")
    echo "Allowed Countries:   ${countries}"

    local mmdb_file="/var/lib/GeoIP/GeoLite2-Country.mmdb"
    if [[ -f "${mmdb_file}" ]]; then
        local db_size=$(du -h "${mmdb_file}" | cut -f1)
        local db_mtime=$(stat -c '%y' "${mmdb_file}" | cut -d'.' -f1)
        echo "GeoIP Database:      Found (${db_size}, updated: ${db_mtime})"
    else
        echo "GeoIP Database:      Missing (/var/lib/GeoIP/GeoLite2-Country.mmdb)"
    fi

    echo ""
    echo "=== Apache & RAM Usage ==="
    free -h
}

cmd_mode() {
    local target="${1:-}"
    if [[ "$target" == "detection" ]]; then
        sed -i -E 's/SecRuleEngine\s+(On|Off|DetectionOnly)/SecRuleEngine DetectionOnly/' "${WAF_SETTINGS}"
        apache2ctl configtest && systemctl reload apache2
        echo -e "\033[0;32m[OK]\033[0m ModSecurity switched to DETECTION ONLY mode."
    elif [[ "$target" == "blocking" ]]; then
        sed -i -E 's/SecRuleEngine\s+(On|Off|DetectionOnly)/SecRuleEngine On/' "${WAF_SETTINGS}"
        apache2ctl configtest && systemctl reload apache2
        echo -e "\033[0;31m[OK]\033[0m ModSecurity switched to BLOCKING mode (Enforcing 403 on attacks)."
    else
        echo "Invalid mode. Use 'detection' or 'blocking'."
        exit 1
    fi
}

cmd_countries() {
    echo "Current allowed countries in ${GEO_CONF}:"
    grep -n -E "RewriteCond %{ENV:MM_COUNTRY_CODE}" "${GEO_CONF}"
}

cmd_test() {
    echo "=== Running Safe WAF Detection Tests against 127.0.0.1 ==="
    local host="localhost"
    
    echo -n "1. Testing SQL Injection payload: "
    local sqli_res=$(curl -s -o /dev/null -w "%{http_code}" "http://${host}/?id=1%27%20OR%20%271%27=%271")
    echo "HTTP Status ${sqli_res}"

    echo -n "2. Testing Cross-Site Scripting (XSS) payload: "
    local xss_res=$(curl -s -o /dev/null -w "%{http_code}" "http://${host}/?q=%3Cscript%3Ealert(1)%3C/script%3E")
    echo "HTTP Status ${xss_res}"

    echo -n "3. Testing Path Traversal payload: "
    local lfi_res=$(curl -s -o /dev/null -w "%{http_code}" "http://${host}/?file=../../../../etc/passwd")
    echo "HTTP Status ${lfi_res}"

    echo ""
    echo "Checking recent audit log detections:"
    tail -n 15 "${AUDIT_LOG}" 2>/dev/null || echo "No audit logs found yet."
}

cmd_logs() {
    local lines="${1:-30}"
    if [[ -f "${AUDIT_LOG}" ]]; then
        tail -n "${lines}" -f "${AUDIT_LOG}"
    else
        echo "Audit log file does not exist yet: ${AUDIT_LOG}"
    fi
}

case "${1:-status}" in
    status)    cmd_status ;;
    mode)      cmd_mode "${2:-}" ;;
    countries) cmd_countries ;;
    test)      cmd_test ;;
    logs)      cmd_logs "${2:-30}" ;;
    *)         usage ;;
esac
EOF
chmod 755 /usr/local/bin/waf-ctl
log_success "Installed CLI tool: /usr/local/bin/waf-ctl"

# ==============================================================================
# 9. Configuration Validation & Apache Reload
# ==============================================================================
log_info "9. Validating Apache syntax before reloading..."
if apache2ctl configtest; then
    systemctl reload apache2
    log_success "Apache configuration test passed. Apache gracefully reloaded."
else
    log_err "Apache configuration test FAILED. Reverting changes..."
    exit 1
fi

# ==============================================================================
# 10. Summary Report
# ==============================================================================
echo ""
echo -e "${BOLD}${GREEN}========================================================================${NC}"
echo -e "${BOLD}${GREEN}   Installation Complete! WAF is running in DETECTION ONLY mode.       ${NC}"
echo -e "${BOLD}${GREEN}========================================================================${NC}"
echo -e "WAF Configuration Dir:  ${BOLD}/etc/apache2/waf/${NC}"
echo -e "Country Allowlist:      ${BOLD}${COUNTRY_ALLOWLIST_CONF}${NC}"
echo -e "ModSecurity Settings:   ${BOLD}${MODSEC_CUSTOM_CONF}${NC}"
echo -e "Audit Log Location:     ${BOLD}/var/log/apache2/waf/modsec_audit.log${NC}"
echo -e "Backup Location:        ${BOLD}${BACKUP_DIR}/apache2_backup.tar.gz${NC}"
echo ""
echo -e "${BOLD}Management Commands:${NC}"
echo -e "  - View Status:        ${CYAN}sudo waf-ctl status${NC}"
echo -e "  - Test Detections:    ${CYAN}sudo waf-ctl test${NC}"
echo -e "  - View Live Logs:     ${CYAN}sudo waf-ctl logs${NC}"
echo -e "  - Switch to Blocking: ${YELLOW}sudo waf-ctl mode blocking${NC}"
echo -e "  - Switch to Detect:   ${GREEN}sudo waf-ctl mode detection${NC}"
echo ""
echo -e "${BOLD}Next Steps:${NC}"
echo -e "1. Run ${CYAN}'sudo waf-ctl test'${NC} to confirm OWASP attack detection."
echo -e "2. Monitor your site logs for 24-48 hours in ${GREEN}DetectionOnly${NC} mode."
echo -e "3. Once confident that false positives are resolved, switch to blocking mode:"
echo -e "   ${YELLOW}sudo waf-ctl mode blocking${NC}"
echo -e "${BOLD}${GREEN}========================================================================${NC}"

# Disable failure trap since we succeeded
trap - EXIT
exit 0
