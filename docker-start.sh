#!/bin/bash
set -e

echo "========================================="
echo "  WINNER GYM - Starting Docker Containers"
echo "========================================="

# Check Docker daemon
if ! docker info > /dev/null 2>&1; then
    echo "Error: Docker is not running. Please start Docker first."
    exit 1
fi

# Ensure .env.docker exists
if [ ! -f ".env.docker" ]; then
    echo "Creating .env.docker from .env.docker.example..."
    cp .env.docker.example .env.docker
fi

# Build and start containers
echo "Building and starting containers..."
docker compose up -d --build

echo "Waiting for database initialization..."
sleep 5

# Run migrations and seeders
echo "Running migrations..."
docker compose exec app php artisan migrate --force

echo "Running default seeders..."
docker compose exec app php artisan db:seed --force

echo "Building front-end assets..."
docker compose exec app npm run build

echo ""
echo "========================================="
echo "  WINNER GYM is now RUNNING!"
echo "  URL: http://localhost:8000"
echo "========================================="
echo ""
echo "To create an Owner account, run:"
echo "docker compose exec app php artisan winner-gym:create-owner --generate-password --name=\"اسم المالك\" owner"
echo ""
echo "To run tests inside Docker:"
echo "docker compose exec app php artisan test"
