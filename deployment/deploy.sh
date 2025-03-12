#!/bin/bash

echo "🚀 Starting Full Deployment Process on DigitalOcean"

# Define project directory
export PROJECT_DIR="/var/www/user-service"
echo "📂 Using PROJECT_DIR: $PROJECT_DIR"

# Ensure necessary directories exist
if [ ! -d "$PROJECT_DIR" ]; then
  echo "📂 Creating project directory..."
  sudo mkdir -p $PROJECT_DIR
  sudo chown -R $USER:$USER $PROJECT_DIR
else
  echo "✅ Project directory already exists: $PROJECT_DIR"
fi

cd $PROJECT_DIR

# Clone or Update Repository
if [ ! -d ".git" ]; then
  echo "📥 Cloning Repository..."
  git clone https://github.com/minthiha-ai/laravel-docker-deployment.git $PROJECT_DIR
else
  echo "📥 Pulling Latest Code..."
  git stash
  git pull --rebase origin main
fi

# Ensure .env is correctly set
if [ -f "deployment/.env.prod" ]; then
  echo "🔄 Setting up environment variables..."
  cp deployment/.env.prod $PROJECT_DIR/.env
  echo "✅ Environment variables set up at $PROJECT_DIR/.env"
else
  echo "❌ Error: deployment/.env.prod file is missing!"
  exit 1
fi

# Stop Old Containers
echo "🐳 Stopping existing containers..."
docker-compose -f deployment/docker-compose.prod.yml down --remove-orphans

# Clean up old Docker images
echo "🧹 Cleaning up old Docker images..."
docker system prune -af

# Pull Latest Docker Image
echo "🐳 Pulling latest Docker image..."
docker pull ace009/user-service-app:latest

# Start New Containers
echo "🐳 Starting new containers..."
docker-compose -f deployment/docker-compose.prod.yml up -d --remove-orphans

# Verify Running Containers
echo "📊 Checking running containers..."
docker ps -a

# Find correct container name dynamically
APP_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'user-service-app')

if [ -z "$APP_CONTAINER" ]; then
    echo "❌ Error: Could not find running Laravel container!"
    docker ps -a
    exit 1
fi

# Run Database Migrations
echo "🔄 Running database migrations..."
docker exec $APP_CONTAINER php artisan migrate --force

# Fix Permissions
echo "🔧 Fixing storage & permissions..."
docker exec $APP_CONTAINER chmod -R 777 storage bootstrap/cache

echo "✅ Deployment complete!"
