#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📄 Creating .env from .env.example..."
    cp .env.example .env
    echo "✅ .env file created"
fi

# Wait for database
echo "⏳ Waiting for database connection..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do 
    sleep 1
done
echo "✅ Database connection established"

# Generate key ONLY if APP_KEY is not set via environment variable
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction
    echo "✅ Application key generated"
else
    echo "✅ Using provided APP_KEY from environment"
fi

# Rest of your script...
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
apache2-foreground
