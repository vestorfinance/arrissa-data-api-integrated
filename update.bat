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
exit /b 0
