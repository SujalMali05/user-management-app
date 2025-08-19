#!/bin/bash
set -e

echo "🚀 Starting Laravel application (temporarily skipping database)..."

# Only generate APP_KEY if it's not already set
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating new application key..."
    php artisan key:generate --force --no-interaction
    echo "✅ Application key generated"
else
    echo "✅ Using existing APP_KEY"
fi

# Temporarily skip database operations
echo "⚠️  Temporarily skipping database operations"
echo "   - Database connection check: SKIPPED"
echo "   - Database migrations: SKIPPED"
echo "   - Database seeding: SKIPPED"

# Optimize Laravel application
echo "⚡ Optimizing Laravel application..."
php artisan route:cache --no-interaction || echo "Route cache skipped"
php artisan view:cache --no-interaction || echo "View cache skipped"
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
