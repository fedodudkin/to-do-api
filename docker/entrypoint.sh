#!/bin/sh
set -e
cd /var/www/html
if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --optimize-autoloader
fi
php bin/setup-db.php
chown -R www-data:www-data /var/www/html/database /var/www/html/logs 2>/dev/null || true
exec docker-php-entrypoint "$@"
