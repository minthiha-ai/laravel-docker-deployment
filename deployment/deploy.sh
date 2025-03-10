#!/bin/bash

echo "🚀 Starting Full Deployment Process on DigitalOcean"

# Ensure necessary directories exist
sudo mkdir -p /var/www/laravel-docker
sudo chown -R $USER:$USER /var/www/user-service
cd /var/www/user-service

# Clone or Update Repository
if [ ! -d "$PROJECT_DIR/.git" ]; then
  echo "📥 Cloning Repository..."
  git clone https://github.com/minthiha-ai/laravel-docker-deployment.git /var/www/user-service
else
  echo "📥 Pulling Latest Code..."
  git pull origin main
fi

# Ensure .env is correctly set
echo "🔄 Setting up environment variables..."
cp deployment/.env.prod .env

# Stop Old Containers
echo "🐳 Stopping existing containers..."
docker-compose -f deployment/docker-compose.prod.yml down

# Pull Latest Docker Image
echo "🐳 Pulling latest Docker image..."
docker pull ace009/user-service-app:latest

# Start New Containers
echo "🐳 Starting new containers..."
docker-compose -f deployment/docker-compose.prod.yml up -d --remove-orphans

# Verify Running Containers
echo "📊 Checking running containers..."
docker ps -a

# Run Database Migrations
echo "🔄 Running database migrations..."
docker exec user_service_app php artisan migrate --force

# Fix Permissions
echo "🔧 Fixing storage & permissions..."
docker exec user_service_app chmod -R 777 storage bootstrap/cache

echo "✅ Deployment complete!"
