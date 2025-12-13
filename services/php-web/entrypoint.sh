#!/usr/bin/env bash
set -e

APP_DIR="/var/www/html"
PATCH_DIR="/opt/laravel-patches"

echo "[php] Initializing Laravel application..."

if [ ! -f "$APP_DIR/artisan" ]; then
  echo "[php] Creating Laravel project..."
  composer create-project --no-interaction --prefer-dist laravel/laravel:^11 "$APP_DIR"
  
  # Copy .env if needed
  if [ -f "/var/www/html/.env" ]; then
    echo "[php] Using mounted .env file"
  else
    echo "[php] Creating .env from example..."
    cp "$APP_DIR/.env.example" "$APP_DIR/.env" || true
  fi
  
  # Generate key
  cd "$APP_DIR"
  php artisan key:generate --force || true
fi

if [ -d "$PATCH_DIR" ]; then
  echo "[php] Applying patches..."
  rsync -a "$PATCH_DIR/" "$APP_DIR/"
fi

# Set permissions
chown -R www-data:www-data "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true

echo "[php] Starting php-fpm..."
exec php-fpm -F