#!/bin/sh
set -e
cd /var/www/html

if [ ! -L public/storage ]; then
  php artisan storage:link
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
