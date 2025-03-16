#!/bin/bash

set -e  # Exit immediately if a command fails

echo "🚀 Starting Full Deployment Process on DigitalOcean"

# Ensure script runs from correct directory
cd "$(dirname "$0")"

# Define project directory
export PROJECT_DIR="/var/www/user-service"
echo "📂 Using PROJECT_DIR: $PROJECT_DIR"

# Load MySQL environment variables from .env.prod
if [ -f "$PROJECT_DIR/deployment/.env.prod" ]; then
    set -a
    source "$PROJECT_DIR/deployment/.env.prod"
    set +a
else
    echo "❌ Error: Missing .env.prod file in deployment directory!"
    exit 1
fi

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
    echo "❌ Error: .env file is missing! Copying from .env.prod..."
    cp "$PROJECT_DIR/deployment/.env.prod" "$PROJECT_DIR/src/.env"
    echo "✅ Environment variables copied to src/.env"
else
    echo "✅ .env file already exists in src/"
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

# Ensure MySQL container exists before proceeding
MYSQL_CONTAINER="user_service_mysql"
if ! docker ps --format '{{.Names}}' | grep -q "$MYSQL_CONTAINER"; then
    echo "❌ Error: MySQL container not found!"
    exit 1
fi

echo "⌛ Waiting for MySQL to be ready..."
until docker exec $MYSQL_CONTAINER mysqladmin ping -h "localhost" --silent; do
    echo "⏳ MySQL is still starting..."
    sleep 5
done
echo "✅ MySQL is ready!"

# Check if the root user exists in MySQL before trying to alter it
ROOT_EXISTS=$(docker exec $MYSQL_CONTAINER mysql -u root -sse "
SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = 'root');" 2>/dev/null)

if [ "$ROOT_EXISTS" -eq "1" ]; then
    echo "🔍 MySQL root user exists. Ensuring correct password..."
    docker exec $MYSQL_CONTAINER mysql -u root -e "
    ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';
    ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';
    FLUSH PRIVILEGES;"
    echo "✅ MySQL root password updated successfully."
else
    echo "⚠️ MySQL root user does NOT exist. Skipping password reset."
fi

# Ensure MySQL is Ready
until docker exec $MYSQL_CONTAINER mysqladmin ping -h "localhost" --silent; do
    echo "⏳ Waiting for MySQL..."
    sleep 5
done

# Ensure Database and User Exist
echo "🛢 Checking if database exists..."
DB_EXIST=$(docker exec $MYSQL_CONTAINER mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SHOW DATABASES LIKE '${MYSQL_DATABASE}';" | grep ${MYSQL_DATABASE} || true)

if [ -z "$DB_EXIST" ]; then
    echo "🚀 Creating database: ${MYSQL_DATABASE}"
    docker exec $MYSQL_CONTAINER mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "CREATE DATABASE ${MYSQL_DATABASE};"
else
    echo "✅ Database ${MYSQL_DATABASE} already exists."
fi

# Ensure MySQL User Exists & Set Permissions
echo "🔧 Checking if MySQL user exists..."
USER_EXISTS=$(docker exec $MYSQL_CONTAINER mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -sse "
SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '${MYSQL_USER}');")

if [ "$USER_EXISTS" -eq "0" ]; then
    echo "🚀 Creating MySQL user '${MYSQL_USER}'..."
    docker exec $MYSQL_CONTAINER mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "
    CREATE USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';
    GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;"
else
    echo "✅ MySQL user '${MYSQL_USER}' already exists. Updating password..."
    docker exec $MYSQL_CONTAINER mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "
    DROP USER '${MYSQL_USER}'@'%';
    CREATE USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';
    GRANT ALL PRIVILEGES ON ${MYSQL_DATABASE}.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;"
fi

# Restart MySQL to apply changes
docker restart $MYSQL_CONTAINER
sleep 5

# Check APP_KEY and Generate If Missing
APP_CONTAINER=$(docker ps --format '{{.Names}}' | grep 'user-service-app')
APP_KEY=$(docker exec $APP_CONTAINER sh -c "grep '^APP_KEY=' /var/www/html/.env | cut -d '=' -f2 | tr -d ' '")

if [[ -z "$APP_KEY" || "$APP_KEY" == "null" ]]; then
    echo "🔑 Generating Laravel APP_KEY..."
    docker exec $APP_CONTAINER php artisan key:generate --force
fi

# Fix Permissions (Safer Alternative to chmod 777)
echo "🔧 Setting correct file permissions..."
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/html
docker exec $APP_CONTAINER chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear and Cache Configurations
echo "🔄 Clearing and caching config..."
docker exec $APP_CONTAINER php artisan config:clear
docker exec $APP_CONTAINER php artisan config:cache
docker exec $APP_CONTAINER php artisan route:cache
docker exec $APP_CONTAINER php artisan view:cache

# Ensure Storage Link Exists
echo "🔗 Creating storage symlink..."
docker exec $APP_CONTAINER php artisan storage:link || true

# Run Database Migrations
echo "🔄 Running database migrations..."
docker exec $APP_CONTAINER php artisan migrate --force

# Restart Services
echo "🔄 Restarting application..."
docker restart $APP_CONTAINER
docker restart user_service_nginx

echo "✅ Deployment complete!"
