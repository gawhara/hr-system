@echo off
setlocal

set "APP_DIR=%~dp0"
cd /d "%APP_DIR%"

echo Starting SMARS HR system...
echo Project: %APP_DIR%
echo.

if not exist "artisan" (
    echo ERROR: artisan was not found. Run this file from the Laravel project folder.
    pause
    exit /b 1
)

where php >nul 2>nul
if errorlevel 1 (
    echo ERROR: PHP was not found in PATH.
    echo Expected XAMPP PHP, for example: D:\xampp82\php\php.exe
    pause
    exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
    echo ERROR: npm was not found in PATH.
    pause
    exit /b 1
)

if not exist "vendor\autoload.php" (
    echo ERROR: Composer dependencies are missing. Run composer install first.
    pause
    exit /b 1
)

if not exist "node_modules" (
    echo ERROR: Node dependencies are missing. Run npm install first.
    pause
    exit /b 1
)

start "SMARS HR - Laravel Server" /D "%APP_DIR%" cmd /k "php artisan serve --host=127.0.0.1 --port=8000"
start "SMARS HR - Vite Assets" /D "%APP_DIR%" cmd /k "npm run dev -- --host 127.0.0.1"

timeout /t 3 /nobreak >nul
start "" "http://127.0.0.1:8000/dashboard"

echo.
echo System is starting:
echo   Laravel: http://127.0.0.1:8000
echo   Dashboard: http://127.0.0.1:8000/dashboard
echo.
echo Keep the two server windows open while using the system.
pause
