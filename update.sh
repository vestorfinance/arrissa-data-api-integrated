#!/bin/bash
# update.sh — fix .git ownership then pull latest code
# Called by PHP (runs as www-data), needs sudo access (see sudoers note below)
#
# To allow www-data to run this without a password, add to /etc/sudoers:
#   www-data ALL=(ALL) NOPASSWD: /var/www/html/update.sh
# (adjust path to match your app install location)

REPO_DIR="$(cd "$(dirname "$0")" && pwd)"

# Fix ownership so both www-data and the repo owner can write to .git
chown -R www-data:www-data "$REPO_DIR/.git"
chmod -R g+rwX "$REPO_DIR/.git"

# Run git pull
cd "$REPO_DIR" || exit 1
git pull origin main 2>&1
