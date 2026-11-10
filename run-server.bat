@echo off
title CargoTrace - Web Server (Port 8000)
echo ======================================================================
echo   Starting CargoTrace Web Server...
echo   Open your browser at: http://localhost:8000
echo   Press Ctrl+C to stop the server.
echo ======================================================================
"C:\xampp\php\php.exe" -c ".\php.ini" artisan serve
pause
