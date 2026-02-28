#!/bin/bash
# update-n8n.sh — pull the latest n8n image and recreate the container
# Called by PHP (runs as www-data) via sudo.
#
# Add to /etc/sudoers.d/arrissa-n8n:
#   www-data ALL=(ALL) NOPASSWD: /opt/n8n/update-n8n.sh
# (adjust path if your compose file lives elsewhere)

COMPOSE_DIR="/opt/n8n"

cd "$COMPOSE_DIR" || { echo "ERROR: $COMPOSE_DIR not found. Is n8n installed at /opt/n8n?"; exit 1; }

# Fix ownership so www-data has access
chown -R www-data:www-data "$COMPOSE_DIR"
chmod -R g+rwX "$COMPOSE_DIR"

echo "Pulling latest n8n image..."
docker compose pull n8n 2>&1
if [ $? -ne 0 ]; then
    echo "ERROR: docker compose pull failed"
    exit 1
fi

echo "Recreating container..."
docker compose up -d --force-recreate n8n 2>&1
if [ $? -ne 0 ]; then
    echo "ERROR: docker compose up failed"
    exit 1
fi

# Report new version
NEW_VERSION=$(docker exec n8n n8n --version 2>/dev/null || echo "unknown")
echo "n8n update complete. Version: $NEW_VERSION"
