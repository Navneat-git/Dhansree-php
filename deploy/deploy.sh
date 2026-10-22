#!/usr/bin/env bash

# ==============================================================================
# Dhansree Production Deployment Automation Script
# Optimized for AWS Lightsail (2 GB RAM, 2 vCPUs, No Node.js build process)
# ==============================================================================

set -euo pipefail

APP_DIR="/home/administrator/nodegny/Dhansree-php"
PHP_BIN="/usr/bin/php8.3"
COMPOSER_BIN="composer"

echo "==> [$(date '+%Y-%m-%d %H:%M:%S')] Starting Dhansree Production Deployment..."

cd "$APP_DIR"

# 1. Enter Maintenance Mode (friendly customer message)
echo "==> Entering maintenance mode..."
"$PHP_BIN" artisan down --secret="dhansree-bypass-deploy-2026" --retry=30 || true

# 2. Pull latest release code
echo "==> Pulling latest code from origin/main..."
git fetch origin main
git reset --hard origin/main

# 3. Install/Update PHP dependencies (production only, no dev, optimized autoloader)
echo "==> Installing Composer production dependencies..."
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet

# 4. Run database migrations safely
echo "==> Executing database migrations..."
"$PHP_BIN" artisan migrate --force

# 5. Build/Verify Tailwind CSS via standalone CLI (No node_modules / npm needed!)
echo "==> Compiling Tailwind CSS..."
bash scripts/build-css.sh

# 6. Cache Laravel Configurations, Routes, and Blade Views
echo "==> Optimizing and caching Laravel runtime..."
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan event:cache

# 7. Ensure storage symlink is active
echo "==> Ensuring public storage link..."
"$PHP_BIN" artisan storage:link --quiet || true

# 8. Restart background Queue Workers to pick up updated code
echo "==> Restarting queue workers..."
"$PHP_BIN" artisan queue:restart

# 9. Reload PHP-FPM pool smoothly without dropping active web requests
if command -v systemctl >/dev/null 2>&1; then
    echo "==> Reloading PHP-FPM service..."
    sudo systemctl reload php8.3-fpm || true
fi

# 10. Exit Maintenance Mode
echo "==> Bringing application back online..."
"$PHP_BIN" artisan up

echo "==> [$(date '+%Y-%m-%d %H:%M:%S')] Deployment successfully completed!"
