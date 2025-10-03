@echo off
REM Windows batch commands for Symfony Orders Application

echo Symfony Orders Application - Windows Commands
echo ============================================

if "%1"=="up" goto up
if "%1"=="down" goto down
if "%1"=="build" goto build
if "%1"=="test" goto test
if "%1"=="migrate" goto migrate
if "%1"=="seed-manticore" goto seed-manticore
if "%1"=="cache-clear" goto cache-clear
if "%1"=="logs" goto logs
if "%1"=="shell" goto shell
if "%1"=="dev" goto dev
if "%1"=="check-health" goto check-health
if "%1"=="cs-fix" goto cs-fix
if "%1"=="cs-check" goto cs-check
if "%1"=="help" goto help

:help
echo.
echo Available commands:
echo   up           - Start all services
echo   down         - Stop all services
echo   build        - Rebuild containers
echo   test         - Run tests
echo   migrate      - Run database migrations
echo   seed-manticore - Seed Manticore index
echo   cache-clear  - Clear application cache
echo   logs         - Show logs
echo   shell        - Access app container
echo   dev          - Start development environment
echo   check-health - Check health of all services
echo   cs-fix       - Fix code style
echo   cs-check     - Check code style
echo   help         - Show this help
echo.
echo Usage: %0 [command]
echo Example: %0 up
goto end

:up
echo Starting all services...
docker-compose -f docker-compose.windows.yml up -d
goto end

:down
echo Stopping all services...
docker-compose -f docker-compose.windows.yml down
goto end

:build
echo Rebuilding containers...
docker-compose -f docker-compose.windows.yml build
goto end

:test
echo Running tests...
docker-compose -f docker-compose.windows.yml exec app ./vendor/bin/phpunit
goto end

:migrate
echo Running database migrations...
docker-compose -f docker-compose.windows.yml exec app bin/console doctrine:migrations:migrate --no-interaction
goto end

:seed-manticore
echo Seeding Manticore index...
docker-compose -f docker-compose.windows.yml exec app bin/console app:seed-manticore
goto end

:cache-clear
echo Clearing application cache...
docker-compose -f docker-compose.windows.yml exec app bin/console cache:clear
goto end

:logs
echo Showing logs...
docker-compose -f docker-compose.windows.yml logs -f
goto end

:shell
echo Accessing app container...
docker-compose -f docker-compose.windows.yml exec app /bin/bash
goto end

:dev
echo Starting development environment...
docker-compose -f docker-compose.windows.yml up -d
echo Waiting for services to be ready...
timeout /t 10 /nobreak > nul
echo Running migrations...
docker-compose -f docker-compose.windows.yml exec app bin/console doctrine:migrations:migrate --no-interaction
echo Seeding Manticore index...
docker-compose -f docker-compose.windows.yml exec app bin/console app:seed-manticore
echo Development environment is ready!
echo API Documentation: http://localhost:8080/api/docs
goto end

:check-health
echo Checking health of all services...
docker-compose -f docker-compose.windows.yml ps
echo.
echo Checking application health...
curl -f http://localhost:8080/api/docs > nul 2>&1
if %errorlevel%==0 (
    echo ✓ Application is healthy
) else (
    echo ✗ Application is not responding
)
goto end

:cs-fix
echo Fixing code style...
docker-compose -f docker-compose.windows.yml exec app ./vendor/bin/php-cs-fixer fix
goto end

:cs-check
echo Checking code style...
docker-compose -f docker-compose.windows.yml exec app ./vendor/bin/php-cs-fixer fix --dry-run --diff
goto end

:end
