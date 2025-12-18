@echo off
cd /d "C:\wamp64\www\events-scrapper-nodejs"
if not exist "node_modules\" (
    echo Installing dependencies...
    call npm install
    echo.
)
echo Starting Events Scrapper...
echo.
node events.js
pause
