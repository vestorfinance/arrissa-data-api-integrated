#!/bin/bash

# Events Scrapper Launcher for Linux/Mac
# Expected location of the scrapper (update this for your system)
SCRAPPER_DIR="/var/www/html/events-scrapper-nodejs"

# For macOS/Linux with custom paths, update the line above
# Examples:
# SCRAPPER_DIR="/Users/username/www/events-scrapper-nodejs"
# SCRAPPER_DIR="/home/username/www/events-scrapper-nodejs"

# Colors for terminal output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo ""
echo "===================================="
echo -e "${GREEN}Events Scrapper Launcher${NC}"
echo "===================================="
echo ""

# Check if events.js exists in the expected location
if [ -f "$SCRAPPER_DIR/events.js" ]; then
    echo "Found scrapper at: $SCRAPPER_DIR"
    cd "$SCRAPPER_DIR" || {
        echo -e "${RED}Error: Cannot access scrapper directory!${NC}"
        exit 1
    }
else
    echo -e "${RED}ERROR: Events scrapper not found!${NC}"
    echo ""
    echo "Expected location: $SCRAPPER_DIR"
    echo ""
    echo "Please ensure the events scrapper is installed at that location,"
    echo "or update the SCRAPPER_DIR variable in this script."
    echo ""
    if [[ "$OSTYPE" == "darwin"* ]]; then
        read -p "Press Enter to close..."
    fi
    exit 1
fi

# Check if Node.js is installed
if ! command -v node &> /dev/null
then
    echo -e "${RED}Error: Node.js is not installed!${NC}"
    echo "Please install Node.js from https://nodejs.org/"
    if [[ "$OSTYPE" == "darwin"* ]]; then
        read -p "Press Enter to close..."
    fi
    exit 1
fi

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo ""
    echo "===================================="
    echo "Installing dependencies..."
    echo "===================================="
    echo ""
    npm install
    echo ""
    echo "Dependencies installed!"
    echo ""
fi

# Run the events scraper
echo ""
echo "===================================="
echo "Starting Events Scrapper..."
echo "===================================="
echo ""
node events.js

echo ""
echo "===================================="
echo "Scrapper closed."
echo "===================================="
echo ""

# Keep terminal open on Mac
if [[ "$OSTYPE" == "darwin"* ]]; then
    read -p "Press Enter to close..."
fi
