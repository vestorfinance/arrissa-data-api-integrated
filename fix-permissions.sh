#!/bin/bash
# fix-permissions.sh
# Creates queue directories and sets correct ownership/permissions.
#
# Called automatically by update.sh on every git pull (runs as root).
# Can also be run manually: sudo bash fix-permissions.sh

set -e

INSTALL_DIR="/var/www/arrissa"

echo "=== Fixing permissions for $INSTALL_DIR ==="

# Base ownership and permissions
chown -R www-data:www-data "$INSTALL_DIR"
find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
find "$INSTALL_DIR" -type f -exec chmod 644 {} \;

# Writable directories — created if missing, then made group-writable
WRITABLE_DIRS=(
    "$INSTALL_DIR/database"
    "$INSTALL_DIR/market-data-api-v1/queue"
    "$INSTALL_DIR/orders-api-v1/queue"
    "$INSTALL_DIR/symbol-info-api-v1/queue"
    "$INSTALL_DIR/tma-cg-api-v1/queue"
    "$INSTALL_DIR/quarters-theory-api-v1/queue"
    "$INSTALL_DIR/time-machine-ml-api-v1/queue"
    "$INSTALL_DIR/risk-management-api-v1/queue"
    "$INSTALL_DIR/chart-image-api-v1/queue"
    "$INSTALL_DIR/news-api-v1/queue"
    "$INSTALL_DIR/url-api-v1/queue"
)

for dir in "${WRITABLE_DIRS[@]}"; do
    mkdir -p "$dir"
    chmod -R 775 "$dir"
    chown -R www-data:www-data "$dir"
    echo "  Fixed: $dir"
done

echo ""
echo "=== Done. All permissions fixed. ==="
