#!/bin/sh
set -eu

PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$PROJECT_DIR"

if [ ! -f .env ]; then
    echo 'Tracker MCP: manca il file .env del progetto.' >&2
    exit 1
fi

exec php artisan mcp:serve
