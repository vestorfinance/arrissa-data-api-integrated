#!/bin/bash
# fix-permissions.sh
# Run this on your server to fix queue/database directory permissions.
# Usage: sudo bash fix-permissions.sh
# Also called automatically by update.sh on every git pull.

set -e

INSTALL_DIR="/var/www/arrissa"

echo "=== Fixing permissions for $INSTALL_DIR ==="

# Base ownership and permissions
sudo chown -R www-data:www-data "$INSTALL_DIR"
sudo find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
sudo find "$INSTALL_DIR" -type f -exec chmod 644 {} \;

# Ensure writable directories exist and have correct permissions
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
    sudo mkdir -p "$dir"
    sudo chmod -R 775 "$dir"
    sudo chown -R www-data:www-data "$dir"
    echo "  Fixed: $dir"
done

echo ""
echo "=== Done. All permissions fixed. ==="
