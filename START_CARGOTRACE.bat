@echo off
title CargoTrace - Local Development Server
cd /d "C:\xampp\htdocs\cargotrace"
echo ========================================================
echo   Starting CargoTrace Server at http://127.0.0.1:8000
echo ========================================================
echo.
echo Pastikan MySQL di XAMPP sudah dinyalakan!
echo Tekan Ctrl+C untuk menghentikan server.
echo.
start http://127.0.0.1:8000
php artisan serve
pause
