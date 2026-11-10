@echo off
title CargoTrace - Automated Test Suite
echo ======================================================================
echo   Running CargoTrace Automated PHPUnit Test Matrix...
echo ======================================================================
"C:\xampp\php\php.exe" -c ".\php.ini" vendor/phpunit/phpunit/phpunit
pause

