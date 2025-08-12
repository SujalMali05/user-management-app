#!/bin/bash
if [ ! -f .env ]; then cp .env.example .env; fi
echo "Waiting for database..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do sleep 1; done
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
apache2-foreground
