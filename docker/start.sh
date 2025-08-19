#!/bin/bash
set -e

echo "🚀 Starting Laravel application with AWS RDS..."

# Debug: Show environment variables (mask password)
echo "🔍 Database configuration:"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"
echo "DB_PASSWORD: ${DB_PASSWORD:0:3}***${DB_PASSWORD: -3}"

# Check if mysqladmin is available
if ! command -v mysqladmin &> /dev/null; then
    echo "⚠️ mysqladmin not found, installing MySQL client..."
    apt-get update -qq
    apt-get install -y default-mysql-client || apt-get install -y mysql-client
fi

# Test RDS connection from inside container
echo "⏳ Testing RDS connection from inside container..."
TIMEOUT=60
COUNTER=0

while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
    if [ $COUNTER -ge $TIMEOUT ]; then
        echo "❌ RDS database connection timeout after ${TIMEOUT} seconds"
        echo "🔍 Troubleshooting from inside container:"
        
        # Test DNS resolution
        echo "DNS test:"
        nslookup "$DB_HOST" || echo "DNS resolution failed"
        
        # Test port connectivity
        echo "Port connectivity test:"
        timeout 10 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/$DB_PORT" && echo "Port $DB_PORT is open" || echo "Port $DB_PORT is not reachable"
        
        echo "⚠️ Application will start without database functionality"
        break
    fi
    echo "Waiting for RDS database... ($COUNTER/$TIMEOUT)"
    sleep 5
    COUNTER=$((COUNTER + 5))
done

if [ $COUNTER -lt $TIMEOUT ]; then
    echo "✅ RDS database connection established"
    
    # Create database if it doesn't exist
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_DATABASE;" || echo "Database creation skipped"
    
    # Run database migrations
    echo "📊 Running database migrations..."
    php artisan migrate --force --no-interaction || echo "⚠️ Migrations failed"
    
    # Seed the database (optional)
    echo "🌱 Seeding database..."
    php artisan db:seed --force --no-interaction || echo "⚠️ Database seeding skipped"
fi

# Generate APP_KEY if needed
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating new application key..."
    php artisan key:generate --force --no-interaction
fi

# Optimize Laravel application
echo "⚡ Optimizing Laravel application..."
php artisan config:cache --no-interaction || echo "Config cache skipped"
php artisan route:cache --no-interaction || echo "Route cache skipped"
php artisan view:cache --no-interaction || echo "View cache skipped"

# Set proper permissions
echo "🔧 Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

echo "🎉 Laravel application ready!"
echo "🌐 Starting Apache web server..."

# Start Apache in foreground
exec apache2-foreground
