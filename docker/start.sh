#!/bin/bash
set -e

echo "🚀 Starting Laravel application with AWS RDS..."

# Wait for RDS database connection
echo "⏳ Waiting for RDS database connection..."
TIMEOUT=120  # 2 minutes should be enough for RDS
COUNTER=0

while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    if [ $COUNTER -ge $TIMEOUT ]; then
        echo "❌ RDS database connection timeout after ${TIMEOUT} seconds"
        echo "📋 Database connection details:"
        echo "DB_HOST: $DB_HOST"
        echo "DB_PORT: $DB_PORT"
        echo "DB_USERNAME: $DB_USERNAME"
        echo "DB_DATABASE: $DB_DATABASE"
        exit 1
    fi
    echo "Waiting for RDS database... ($COUNTER/$TIMEOUT)"
    sleep 5
    COUNTER=$((COUNTER + 5))
done

echo "✅ RDS database connection established"

# Only generate APP_KEY if it's not already set
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

# Seed the database (optional)
echo "🌱 Seeding database..."
php artisan db:seed --force --no-interaction || echo "⚠️  Database seeding skipped (seeders may not exist)"
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

echo "🎉 Laravel application ready with AWS RDS!"
echo "🌐 Starting Apache web server..."

# Start Apache in foreground
exec apache2-foreground
