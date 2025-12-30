#!/bin/bash
# Railway deployment script

echo "🚀 Starting deployment..."

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# Clear and cache config
echo "⚙️ Caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Run seeders
echo "🌱 Running seeders..."
php artisan db:seed --force

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo "✅ Deployment completed!"
