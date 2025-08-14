#!/bin/bash
set -e

echo "🚀 Starting Laravel application..."

# Wait for database connection with increased timeout
echo "⏳ Waiting for database connection..."
TIMEOUT=300  # Increased to 5 minutes for MySQL 8.0 initialization
COUNTER=0

while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    if [ $COUNTER -ge $TIMEOUT ]; then
        echo "❌ Database connection timeout after ${TIMEOUT} seconds"
        echo "📋 Showing database connection details for debugging:"
        echo "DB_HOST: $DB_HOST"
        echo "DB_USERNAME: $DB_USERNAME"
        echo "DB_DATABASE: $DB_DATABASE"
        exit 1
    fi
    echo "Database not ready, waiting... ($COUNTER/$TIMEOUT)"
    sleep 2  # Check every 2 seconds instead of 1 (reduces log spam)
    COUNTER=$((COUNTER + 2))
done

echo "✅ Database connection established"

# Only generate APP_KEY if it's not already set and .env doesn't have one
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating new application key..."
    php artisan key:generate --force --no-interaction
    echo "✅ Application key generated"
else
    echo "✅ Using existing APP_KEY"
fi

# Run database migrations
echo "📊 Running database migrations..."
php artisan migrate --force --no-interaction
echo "✅ Migrations completed"

# Seed the database
echo "🌱 Seeding database..."
php artisan db:seed --force --no-interaction
echo "✅ Database seeding completed"

# Optimize Laravel application
echo "⚡ Optimizing Laravel application..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
echo "✅ Application optimized"

# Set proper permissions
echo "🔧 Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache
echo "✅ Permissions set"

echo "🎉 Laravel application ready!"
echo "🌐 Starting Apache web server..."

# Start Apache in foreground
exec apache2-foreground
