#!/usr/bin/env bash
# Deploy spektrumX di server production.
# Pakai: ./deploy.sh            (PHP-FPM default php8.3-fpm)
#        PHP_FPM=php8.2-fpm ./deploy.sh
set -euo pipefail

cd "$(dirname "$0")"
PHP_FPM="${PHP_FPM:-php8.3-fpm}"

echo "==> Ambil kode terbaru"
git pull --ff-only

echo "==> Dependensi PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migrasi database"
php artisan migrate --force

echo "==> Build aset"
npm ci --no-audit --no-fund
npm run build

echo "==> Cache Laravel (config, route, view, event)"
php artisan optimize:clear
php artisan optimize

echo "==> Reload ${PHP_FPM} (kosongkan OPcache agar kode baru terbaca)"
sudo systemctl reload "${PHP_FPM}"

echo "==> Selesai: $(git log --oneline -1)"
