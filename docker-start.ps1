# WINNER GYM - Docker Quick Start (PowerShell)
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  WINNER GYM - Starting Docker Containers" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

# Check if Docker is running
docker info > $null 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Docker is not running. Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}

# Ensure .env.docker exists
if (-not (Test-Path ".env.docker")) {
    Write-Host "Creating .env.docker from .env.docker.example..." -ForegroundColor Yellow
    Copy-Item ".env.docker.example" ".env.docker"
}

# Build and start containers
Write-Host "Building and starting containers..." -ForegroundColor Green
docker compose up -d --build

Write-Host "Waiting for database initialization..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

# Run migrations and seeders
Write-Host "Running migrations..." -ForegroundColor Green
docker compose exec app php artisan migrate --force

Write-Host "Running default seeders..." -ForegroundColor Green
docker compose exec app php artisan db:seed --force

# Install dependencies if not already installed inside container
Write-Host "Building front-end assets..." -ForegroundColor Green
docker compose exec app npm run build

Write-Host ""
Write-Host "=========================================" -ForegroundColor Green
Write-Host "  WINNER GYM is now RUNNING!" -ForegroundColor Green
Write-Host "  URL: http://localhost:8090" -ForegroundColor Yellow
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""
Write-Host "To create an Owner account, run:" -ForegroundColor Cyan
Write-Host "docker compose exec app php artisan winner-gym:create-owner --generate-password --name=""اسم المالك"" owner" -ForegroundColor White
Write-Host ""
Write-Host "To run tests inside Docker:" -ForegroundColor Cyan
Write-Host "docker compose exec app php artisan test" -ForegroundColor White
