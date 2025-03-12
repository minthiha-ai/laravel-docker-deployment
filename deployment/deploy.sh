#!/bin/bash

echo "🚀 Starting Full Deployment Process on DigitalOcean"

# Define project directory
export PROJECT_DIR="/var/www/user-service"
echo "📂 Using PROJECT_DIR: $PROJECT_DIR"

# Ensure necessary directories exist and set correct ownership
if [ ! -d "$PROJECT_DIR" ]; then
  echo "📂 Creating project directory..."
  sudo mkdir -p $PROJECT_DIR
  sudo chown -R $(whoami):$(whoami) $PROJECT_DIR
else
  echo "✅ Project directory already exists: $PROJECT_DIR"
fi

cd $PROJECT_DIR

# Ensure Docker and Docker Compose are installed
if ! command -v docker &> /dev/null; then
    echo "🐳 Docker is not installed. Installing now..."
    sudo apt update && sudo apt upgrade -y
    sudo apt install -y docker.io docker-compose
    sudo systemctl enable docker
    sudo systemctl start docker
fi

# Ensure the correct project directory
cd $PROJECT_DIR

# Clone or Update Repository
if [ ! -d ".git" ]; then
  echo "📥 Cloning Repository..."
  git clone https://github.com/minthiha-ai/laravel-docker-deployment.git $PROJECT_DIR
else
  echo "📥 Pulling Latest Code..."
  git reset --hard origin/main
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

# Debug Docker Image Name
echo "🔍 Checking Docker Image Name..."
export DOCKER_IMAGE="ace009/user-service-app:latest"
echo "Using DOCKER_IMAGE: $DOCKER_IMAGE"

# Debugging: Check if the variable is set
if [ -z "$DOCKER_IMAGE" ]; then
    echo "❌ ERROR: DOCKER_IMAGE variable is not set properly!"
    exit 1
fi

# Pull Latest Docker Image
echo "🐳 Pulling latest Docker image..."
docker pull $DOCKER_IMAGE

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

# Wait for MySQL to be ready
MYSQL_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'user-service-mysql')
if [ -z "$MYSQL_CONTAINER" ]; then
    echo "❌ Error: MySQL container not found!"
    exit 1
fi

echo "⌛ Waiting for MySQL to be ready..."
until docker exec $MYSQL_CONTAINER mysqladmin ping -h "localhost" --silent; do
    echo "⏳ MySQL is not ready yet..."
    sleep 5
done
echo "✅ MySQL is ready!"

# Ensure Laravel APP_KEY is set
echo "🔑 Generating Laravel APP_KEY..."
docker exec $APP_CONTAINER php artisan key:generate --force

# Clear and Cache Configuration
echo "🔄 Clearing and caching config..."
docker exec $APP_CONTAINER php artisan config:clear
docker exec $APP_CONTAINER php artisan config:cache

# Ensure storage is linked properly
echo "🔗 Linking storage..."
docker exec $APP_CONTAINER php artisan storage:link

# Run Database Migrations
echo "🔄 Running database migrations..."
docker exec $APP_CONTAINER php artisan migrate --force

# Fix Permissions
echo "🔧 Fixing storage & permissions..."
docker exec $APP_CONTAINER chmod -R 777 storage bootstrap/cache

# Restart Services
echo "🔄 Restarting application..."
docker restart $APP_CONTAINER

echo "✅ Deployment complete!"
