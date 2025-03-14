#!/bin/bash

set -e  # Exit immediately if a command fails

echo "🚀 Starting Full Deployment Process on DigitalOcean"

# Define project directory
export PROJECT_DIR="/var/www/user-service"
echo "📂 Using PROJECT_DIR: $PROJECT_DIR"

# Load MySQL environment variables from .env.prod
set -a
source deployment/.env.prod
set +a

# Ensure necessary directories exist
if [ ! -d "$PROJECT_DIR" ]; then
  echo "📂 Creating project directory..."
  sudo mkdir -p $PROJECT_DIR
  sudo chown -R $(whoami):$(whoami) $PROJECT_DIR
else
  echo "✅ Project directory already exists: $PROJECT_DIR"
fi

if [ ! -d "$PROJECT_DIR/src" ]; then
  echo "❌ Error: Laravel src directory is missing!"
  exit 1
fi

cd $PROJECT_DIR

# Ensure Docker and Docker Compose are installed
if ! command -v docker &> /dev/null; then
    echo "🐳 Installing Docker..."
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

# Ensure .env file is in src/
if [ ! -f "$PROJECT_DIR/src/.env" ]; then
    echo "🔄 Copying production .env..."
    cp deployment/.env.prod src/.env
fi

# Stop Old Containers
docker-compose -f deployment/docker-compose.prod.yml down --remove-orphans || true

# Clean up Docker
docker system prune -af || true

# Ensure valid Docker Image
export DOCKER_IMAGE="ace009/user-service-app:latest"
if [ -z "$DOCKER_IMAGE" ]; then
    echo "❌ ERROR: DOCKER_IMAGE variable is not set properly!"
    exit 1
fi

docker pull $DOCKER_IMAGE
docker-compose -f deployment/docker-compose.prod.yml up -d --remove-orphans

# Ensure MySQL is Ready
MYSQL_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'user-service-mysql')
until docker exec $MYSQL_CONTAINER mysqladmin ping -h "localhost" --silent; do
    sleep 5
done

# Ensure Database and User Exist
docker exec $MYSQL_CONTAINER mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';
ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS ${MYSQL_DATABASE};"

docker restart user_service_mysql
sleep 5

# Check APP_KEY
APP_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'user-service-app')
APP_KEY=$(docker exec $APP_CONTAINER sh -c "grep APP_KEY /var/www/html/.env | cut -d '=' -f2")
if [[ -z "$APP_KEY" || "$APP_KEY" == "null" ]]; then
    docker exec $APP_CONTAINER php artisan key:generate --force
fi

# Fix Permissions
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/html
docker exec $APP_CONTAINER chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache Config & Routes
docker exec $APP_CONTAINER php artisan config:clear
docker exec $APP_CONTAINER php artisan config:cache
docker exec $APP_CONTAINER php artisan route:cache
docker exec $APP_CONTAINER php artisan view:cache

docker exec $APP_CONTAINER php artisan storage:link || true
docker exec $APP_CONTAINER php artisan migrate --force

docker restart $APP_CONTAINER
docker restart user_service_nginx

echo "✅ Deployment complete!"
