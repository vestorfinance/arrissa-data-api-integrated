#!/bin/bash
# update.sh — fix .git ownership then pull latest code
# Called by PHP (runs as www-data), needs sudo access (see sudoers note below)
#
# To allow www-data to run this without a password, add to /etc/sudoers:
#   www-data ALL=(ALL) NOPASSWD: /var/www/html/update.sh
# (adjust path to match your app install location)

REPO_DIR="$(cd "$(dirname "$0")" && pwd)"

# This script is invoked as: sudo bash update.sh (from update-app.php)
# It therefore runs as root — no internal sudo calls needed.

# Fix ownership so both www-data and the repo owner can write to .git
chown -R www-data:www-data "$REPO_DIR/.git"
chmod -R g+rwX "$REPO_DIR/.git"

cd "$REPO_DIR" || exit 1

# Discard any local changes to tracked files so pull always succeeds
git reset --hard HEAD 2>&1
git clean -fd 2>&1

# Pull latest code
git pull origin main 2>&1

# Create queue directories and fix permissions
echo "Fixing permissions..."
bash "$REPO_DIR/fix-permissions.sh" 2>&1

# Run any pending database migrations
echo "Running database migrations..."
php "$REPO_DIR/database/run-migrations.php" 2>&1

# Gracefully reload Apache so new code is picked up without dropping live connections
if command -v apache2ctl >/dev/null 2>&1; then
    apache2ctl graceful 2>&1
    echo "Apache graceful reload triggered."
elif command -v systemctl >/dev/null 2>&1; then
    systemctl reload apache2 2>&1
    echo "Apache reloaded via systemctl."
else
    echo "WARNING: Could not detect Apache. Restart it manually."
fi
