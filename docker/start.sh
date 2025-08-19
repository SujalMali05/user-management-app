#!/bin/bash
set -e

echo "🚀 Starting Laravel application with AWS RDS..."

# Wait for network to be ready
sleep 10

# Install MySQL client and network tools
echo "🔧 Installing required tools..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y default-mysql-client dnsutils iputils-ping curl netcat-openbsd

# Debug environment variables (mask password)
echo "🔍 Database configuration:"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE" 
echo "DB_USERNAME: $DB_USERNAME"

# Advanced connection testing with multiple approaches
echo "🔍 Testing network connectivity..."

# Test 1: Basic ping
if ping -c 2 -W 3 8.8.8.8 > /dev/null 2>&1; then
    echo "✅ Internet connectivity working"
else
    echo "❌ No internet connectivity"
fi

# Test 2: DNS resolution
echo "🔍 Testing DNS resolution for $DB_HOST..."
if nslookup "$DB_HOST" > /dev/null 2>&1; then
    echo "✅ DNS resolution successful"
else
    echo "❌ DNS resolution failed"
    # Try alternative DNS
    echo "nameserver 8.8.8.8" > /etc/resolv.conf
    echo "nameserver 8.8.4.4" >> /etc/resolv.conf
fi

# Test 3: Port connectivity using multiple methods
echo "🔍 Testing port connectivity to $DB_HOST:$DB_PORT..."

# Method 1: netcat
if nc -z -w5 "$DB_HOST" "$DB_PORT" 2>/dev/null; then
    echo "✅ Port $DB_PORT is reachable (netcat)"
    PORT_REACHABLE=true
else
    echo "❌ Port $DB_PORT not reachable (netcat)"
    PORT_REACHABLE=false
fi

# Method 2: bash TCP check (fallback)
if [ "$PORT_REACHABLE" = false ]; then
    if timeout 10 bash -c "exec 3<>/dev/tcp/$DB_HOST/$DB_PORT && echo 'Connected' >&3 && cat <&3" 2>/dev/null; then
        echo "✅ Port $DB_PORT is reachable (bash TCP)"
        PORT_REACHABLE=true
    else
        echo "❌ Port $DB_PORT not reachable (bash TCP)"
    fi
fi

# Extended database connection attempts
echo "⏳ Attempting database connection (5 minute timeout)..."
TIMEOUT=300
COUNTER=0
CONNECTION_SUCCESS=false

while [ $COUNTER -lt $TIMEOUT ]; do
    # Try MySQL connection
    if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --connect-timeout=10 --silent 2>/dev/null; then
        echo "✅ MySQL connection successful!"
        CONNECTION_SUCCESS=true
        break
    fi
    
    # Progress indicator every 30 seconds
    if [ $((COUNTER % 30)) -eq 0 ] && [ $COUNTER -gt 0 ]; then
        echo "⏳ Still attempting connection... ${COUNTER}/${TIMEOUT}s elapsed"
    fi
    
    sleep 5
    COUNTER=$((COUNTER + 5))
done

# Database setup if connection is successful
if [ "$CONNECTION_SUCCESS" = true ]; then
    echo "✅ Database connection established!"
    
    # Ensure database exists
    echo "🔍 Ensuring database '$DB_DATABASE' exists..."
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`;" 2>/dev/null || echo "Database creation command executed"
    
    # Wait a moment for database to be ready
    sleep 5
    
    # Run migrations with detailed output
    echo "📊 Running database migrations..."
    if php artisan migrate --force --no-interaction --verbose; then
        echo "✅ Database migrations completed successfully"
        
        # Run seeders
        echo "🌱 Seeding database with default data..."
        if php artisan db:seed --force --no-interaction --verbose; then
            echo "✅ Database seeding completed successfully"
        else
            echo "⚠️ Database seeding failed, but continuing..."
            php artisan db:seed --force --no-interaction --verbose || true
        fi
    else
        echo "❌ Database migrations failed"
        # Show more details
        php artisan migrate:status || true
    fi
    
    # Verify setup
    echo "🔍 Verifying database setup..."
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "USE \`$DB_DATABASE\`; SHOW TABLES;" 2>/dev/null || echo "Could not verify tables"
    
else
    echo "❌ Could not establish database connection after $TIMEOUT seconds"
    echo "Application will start but database functionality will be limited"
    
    # Additional diagnostics
    echo "🔍 Final diagnostic information:"
    echo "- Host reachable: $(ping -c 1 -W 3 "$DB_HOST" > /dev/null 2>&1 && echo 'YES' || echo 'NO')"
    echo "- Port open: $PORT_REACHABLE"
    echo "- DNS resolution: $(nslookup "$DB_HOST" > /dev/null 2>&1 && echo 'YES' || echo 'NO')"
fi

# Generate APP_KEY if needed
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction
fi

# Cache configuration (skip if database failed)
if [ "$CONNECTION_SUCCESS" = true ]; then
    echo "⚡ Optimizing application..."
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
else
    echo "⚡ Basic optimization (database-free mode)..."
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
fi

# Set permissions
echo "🔧 Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

echo "🎉 Application startup complete!"
echo "🌐 Starting Apache web server..."

# Start Apache
exec apache2-foreground
