@echo off
:: update.bat — Windows equivalent of update.sh
:: Resets any local changes and pulls the latest code from origin/main
:: Run from any location; uses its own directory as the repo root.

cd /d "%~dp0"

echo Resetting local changes...
git reset --hard HEAD
if %ERRORLEVEL% neq 0 (
    echo ERROR: git reset failed
    exit /b %ERRORLEVEL%
)

echo Cleaning untracked files...
git clean -fd
if %ERRORLEVEL% neq 0 (
    echo ERROR: git clean failed
    exit /b %ERRORLEVEL%
)

echo Pulling latest code from origin/main...
git pull origin main
if %ERRORLEVEL% neq 0 (
    echo ERROR: git pull failed
    exit /b %ERRORLEVEL%
)

echo Update complete.

echo Scheduling Apache restart in 3 seconds...
:: Launch a detached background process so the HTTP response is sent first,
:: then Apache is restarted. Tries WAMP service name first, then XAMPP.
start /b cmd /c "timeout /t 3 /nobreak >nul 2>&1 && (sc query wampapache64 >nul 2>&1 && (net stop wampapache64 && net start wampapache64) || (sc query Apache2.4 >nul 2>&1 && (net stop Apache2.4 && net start Apache2.4) || echo Apache service not found))"

exit /b 0
