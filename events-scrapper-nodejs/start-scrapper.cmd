@echo off
title Events Scrapper
color 0A

REM Expected location of the events scrapper
set SCRAPPER_DIR=C:\wamp64\www\events-scrapper-nodejs

echo.
echo ====================================
echo Events Scrapper Launcher
echo ====================================
echo.

REM Check if events.js exists in the expected location
if exist "%SCRAPPER_DIR%\events.js" (
    echo Found scrapper at: %SCRAPPER_DIR%
    cd /d "%SCRAPPER_DIR%"
) else (
    echo ERROR: Events scrapper not found!
    echo.
    echo Expected location: %SCRAPPER_DIR%
    echo.
    echo Please ensure the events scrapper is installed at that location,
    echo or update the SCRAPPER_DIR variable in this script.
    echo.
    pause
    exit /b 1
)

REM Check if node_modules exists
if not exist "node_modules\" (
    echo.
    echo ====================================
    echo Installing dependencies...
    echo ====================================
    echo.
    call npm install
    echo.
    echo Dependencies installed!
    echo.
)

REM Run the events scraper
echo.
echo ====================================
echo Starting Events Scrapper...
echo ====================================
echo.
node events.js

echo.
echo ====================================
echo Scrapper closed.
echo ====================================
echo.
pause
