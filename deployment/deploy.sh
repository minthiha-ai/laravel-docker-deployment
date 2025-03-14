#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status

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

# Ensure necessary directories exist
if [ ! -d "$PROJECT_DIR/src" ]; then
  echo "❌ Error: Laravel src directory is missing!"
  exit 1
fi

cd $PROJECT_DIR

# Ensure Docker and Docker Compose are installed
if ! command -v docker &> /dev/null; then
    echo "🐳 Docker is not installed. Installing now..."
    sudo apt update && sudo apt install -y docker.io docker-compose
    sudo systemctl enable docker
    sudo systemctl start docker
fi

# Clone or Update Repository
if [ ! -d ".git" ]; then
  echo "📥 Cloning Repository..."
  git clone https://github.com/minthiha-ai/laravel-docker-deployment.git $PROJECT_DIR
else
  echo "📥 Pulling Latest Code..."
  git reset --hard origin/main
  git pull --rebase origin main
fi

# Ensure correct `.env` file is inside `src/`
if [ ! -f "$PROJECT_DIR/src/.env" ]; then
    echo "🔄 Copying production environment variables..."
    cp deployment/.env.prod src/.env
    echo "✅ Environment variables copied to src/.env"
else
    echo "✅ .env file already exists in src/"
fi

# Stop Old Containers
echo "🐳 Stopping existing containers..."
docker-compose -f deployment/docker-compose.prod.yml down --remove-orphans || true

# Clean up old Docker images
echo "🧹 Cleaning up old Docker images..."
docker system prune -af || true

# Debug Docker Image Name
echo "🔍 Checking Docker Image Name..."
export DOCKER_IMAGE="ace009/user-service-app:latest"
echo "Using DOCKER_IMAGE: $DOCKER_IMAGE"

# Ensure DOCKER_IMAGE is set
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

# Find running Laravel container
APP_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'user-service-app')
if [ -z "$APP_CONTAINER" ]; then
    echo "❌ Error: Laravel container not running!"
    docker ps -a
    exit 1
fi

# Wait for MySQL
MYSQL_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'user-service-mysql')
if [ -z "$MYSQL_CONTAINER" ]; then
    echo "❌ Error: MySQL container not running!"
    exit 1
fi

echo "⌛ Waiting for MySQL to be ready..."
until docker exec $MYSQL_CONTAINER mysqladmin ping -h "localhost" --silent; do
    echo "⏳ Waiting for MySQL..."
    sleep 5
done
echo "✅ MySQL is ready!"

# Ensure Laravel APP_KEY is set
echo "🔑 Ensuring Laravel APP_KEY is set..."
docker exec $APP_CONTAINER php artisan key:generate --force

# Fix File Permissions
echo "🔧 Setting correct file permissions..."
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/html
docker exec $APP_CONTAINER chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear and Cache Configuration
echo "🔄 Clearing and caching config..."
docker exec $APP_CONTAINER php artisan config:clear
docker exec $APP_CONTAINER php artisan config:cache
docker exec $APP_CONTAINER php artisan route:cache
docker exec $APP_CONTAINER php artisan view:cache

# Ensure storage is linked properly
echo "🔗 Linking storage..."
docker exec $APP_CONTAINER php artisan storage:link || true

# Run Database Migrations
echo "🔄 Running database migrations..."
docker exec $APP_CONTAINER php artisan migrate --force

# Restart Services
echo "🔄 Restarting application..."
docker restart $APP_CONTAINER
docker restart user_service_nginx

echo "✅ Deployment complete!"
