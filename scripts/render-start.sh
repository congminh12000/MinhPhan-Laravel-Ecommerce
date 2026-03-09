#!/usr/bin/env bash
set -euo pipefail

read_app_key() {
  if [ -n "${APP_KEY:-}" ]; then
    printf '%s' "${APP_KEY}"
    return
  fi

  if [ -f .env ]; then
    awk -F= '/^APP_KEY=/{print substr($0, index($0, "=") + 1); exit}' .env
  fi
}

assert_app_key_present() {
  local app_key
  app_key="$(read_app_key | tr -d '"' | tr -d "'" | tr -d '[:space:]')"

  if [ -n "$app_key" ]; then
    return
  fi

  cat >&2 <<'EOF'
APP_KEY is missing. Set a stable APP_KEY in the Railway service variables before starting the app.
Generate one with: php artisan key:generate --show
EOF
  exit 1
}

assert_app_key_present

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
