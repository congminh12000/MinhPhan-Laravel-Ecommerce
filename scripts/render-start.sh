#!/usr/bin/env bash
set -euo pipefail

if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
  sqlite_db="${DB_DATABASE:-$(pwd)/database/database.sqlite}"
  mkdir -p "$(dirname "$sqlite_db")"
  touch "$sqlite_db"
fi

installed_marker="storage/installed"
installed_backup="storage/installed.render.bak"

if [ -f "$installed_marker" ]; then
  mv "$installed_marker" "$installed_backup"
fi

php artisan app:bootstrap-render --no-interaction

if [ -f "$installed_backup" ] && [ ! -f "$installed_marker" ]; then
  mv "$installed_backup" "$installed_marker"
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
