#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Debug: Print Railway environment variables
echo "🔍 DEBUG - Railway Environment Variables:"
echo "MYSQLHOST: ${MYSQLHOST:-NOT SET}"
echo "MYSQLPORT: ${MYSQLPORT:-NOT SET}"
echo "MYSQLDATABASE: ${MYSQLDATABASE:-NOT SET}"
echo "MYSQLUSER: ${MYSQLUSER:-NOT SET}"
echo "MYSQLPASSWORD: ${MYSQLPASSWORD:+SET (hidden)}"
echo "DB_HOST: ${DB_HOST:-NOT SET}"
echo "DB_PORT: ${DB_PORT:-NOT SET}"
echo "DB_DATABASE: ${DB_DATABASE:-NOT SET}"
echo "DB_USERNAME: ${DB_USERNAME:-NOT SET}"
echo "DB_PASSWORD: ${DB_PASSWORD:+SET (hidden)}"
echo ""

# Use MYSQL* variables if DB_* not set (Railway reference format)
DB_HOST="${DB_HOST:-${MYSQLHOST}}"
DB_PORT="${DB_PORT:-${MYSQLPORT}}"
DB_DATABASE="${DB_DATABASE:-${MYSQLDATABASE}}"
DB_USERNAME="${DB_USERNAME:-${MYSQLUSER}}"
DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD}}"

# Create .env from Railway environment variables
echo "📝 Creating .env file..."
cat > .env << EOF
APP_NAME="${APP_NAME:-Kelurahan}"
APP_ENV="${APP_ENV:-production}"
APP_KEY="${APP_KEY:-base64:yS1rjihVcO0xn5CI4ni6QT1ARjFtTWx7asSEYv3RJ/A=}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"

DB_CONNECTION=mysql
DB_HOST=${DB_HOST:-localhost}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-railway}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD}

FRONTEND_URL=${FRONTEND_URL:-*}
SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-*}
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack
LOG_LEVEL=error
EOF

echo "✅ .env file created"
echo "📋 Database config being used:"
echo "DB_HOST: ${DB_HOST:-NOT SET}"
echo "DB_PORT: ${DB_PORT:-3306}"
echo "DB_DATABASE: ${DB_DATABASE:-railway}"
echo "DB_USERNAME: ${DB_USERNAME:-root}"
echo ""

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL..."
max_attempts=30
attempt=0
until php artisan db:show 2>/dev/null || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "MySQL not ready (attempt $attempt/$max_attempts), waiting 2 seconds..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ MySQL connection failed after $max_attempts attempts"
    echo "📋 Current DB config:"
    echo "DB_HOST=$DB_HOST"
    echo "DB_PORT=$DB_PORT"
    echo "DB_DATABASE=$DB_DATABASE"
    echo "DB_USERNAME=$DB_USERNAME"
    exit 1
fi

echo "✅ MySQL is ready!"

# Clear config only (before migration)
echo "🧹 Clearing config cache..."
php artisan config:clear

# Run migrations FIRST (create tables)
echo "🗄️ Running migrations..."
php artisan migrate --force

# Now clear all caches (after tables exist)
echo "🧹 Clearing all caches..."
php artisan cache:clear || echo "⚠️ Cache clear skipped (table may not exist yet)"
php artisan view:clear
php artisan route:clear

# Cache config, routes, views
echo "📦 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run seeders
echo "🌱 Running seeders..."
php artisan db:seed --force || echo "⚠️ Seeder already ran or failed, continuing..."

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link || echo "⚠️ Storage link already exists"

# Start server
echo "✅ Starting Laravel server on port ${PORT}..."
php artisan serve --host=0.0.0.0 --port=${PORT}
