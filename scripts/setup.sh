#!/usr/bin/env sh
# Fresh install / deploy setup for the appointment system (Linux/macOS/CI).
# Idempotent: safe to re-run. Fails fast on any error.
#
# Usage: sh scripts/setup.sh [--skip-tests]
set -eu

SKIP_TESTS=""
if [ "${1:-}" = "--skip-tests" ]; then SKIP_TESTS="1"; fi

for cmd in php composer node npm; do
  command -v "$cmd" >/dev/null 2>&1 || { echo "Missing prerequisite: '$cmd' is not on PATH." >&2; exit 1; }
done

echo "php:      $(php -r 'echo PHP_VERSION;')"
composer --version --no-ansi
echo "node:     $(node -v)"

[ -f artisan ] || { echo "Run this script from the project root (folder containing artisan)." >&2; exit 1; }

composer install --no-interaction --prefer-dist

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Created .env from .env.example — fill in DB_* / REVERB_* / PAYSTACK_* next."
fi

if grep -q '^APP_KEY=$' .env 2>/dev/null || ! grep -q '^APP_KEY=' .env 2>/dev/null; then
  php artisan key:generate --force
fi
php artisan migrate --force
npm install --ignore-scripts
npm run build

php artisan route:list --path=/ --no-ansi | head -5

if [ -z "$SKIP_TESTS" ]; then
  php artisan test --compact
fi

echo "Setup complete. Start local dev with: composer run dev"
