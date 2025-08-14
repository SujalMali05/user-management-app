#!/bin/bash
set -e

echo "🚀 Starting Laravel application (database-free mode)..."

# Only generate APP_KEY if it's not already set
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating new application key..."
    php artisan key:generate --force --no-interaction
    echo "✅ Application key generated"
else
    echo "✅ Using existing APP_KEY"
fi

# Skip database operations for initial deployment
echo "⚠️  Skipping database operations for initial deployment"
echo "   - Database connection check: SKIPPED"
echo "   - Database migrations: SKIPPED" 
echo "   - Database seeding: SKIPPED"

# Optimize Laravel application (skip database-dependent caching)
echo "⚡ Optimizing Laravel application..."
php artisan route:cache --no-interaction || echo "Route cache skipped"
php artisan view:cache --no-interaction || echo "View cache skipped"
# Skip config:cache as it might try to connect to database
echo "✅ Application optimized"

# Set proper permissions
echo "🔧 Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache
echo "✅ Permissions set"

echo "🎉 Laravel application ready (database-free mode)!"
echo "🌐 Starting Apache web server..."

# Start Apache in foreground
exec apache2-foreground
