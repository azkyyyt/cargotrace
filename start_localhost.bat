@echo off
title CargoTrace - Localhost Runner
color 0B
echo ========================================================
echo        CARGOTRACE - GLOBAL GRAIN LOGISTICS PLATFORM
echo               Starting Localhost Services...
echo ========================================================
echo.

:: 1. Check & Start MySQL if not running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is already running.
) else (
    echo [..] Starting MySQL daemon from XAMPP...
    start /B "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini --standalone
    timeout /t 2 >nul
    echo [OK] MySQL started successfully.
)

:: 2. Launch Laravel Web Server
echo.
echo [..] Launching Laravel application on http://127.0.0.1:8000 ...
echo [INFO] Press Ctrl+C anytime to stop the server.
echo.

cd /d "C:\xampp\htdocs\cargotrace"
start "" http://127.0.0.1:8000
php artisan serve --port=8000

pause
