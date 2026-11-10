@echo off
title CargoTrace - Real-Time Maritime Simulator
echo ======================================================================
echo   CargoTrace Real-Time Activity Simulator (10-second loop)
echo   Keeps advancing shipment stages and injecting GPS waypoints!
echo   Watch your dashboard at http://localhost:8000 update in real time!
echo   Press Ctrl+C to stop the simulator.
echo ======================================================================
"C:\xampp\php\php.exe" -c ".\php.ini" artisan cargotrace:simulate --loop=10
pause

