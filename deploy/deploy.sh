#!/usr/bin/env bash
#
# Zero-downtime deploy: each release is built in its own directory next to the live one,
# then `current` is switched to it in one atomic rename. A failed build never touches the
# live site. See docs/production.md for the one-time server setup.
#
#   /var/www/portfolio/
#     releases/20261014120000/   one directory per deploy (the last KEEP are kept)
#     shared/.env                  configuration, linked into every release
#     shared/storage/              uploads, logs, sessions cache: linked into every release
#     current -> releases/...      what nginx, the queue worker and the SSR server run
#
# Usage (as the deploy user, e.g. www-data):
#   REPO=git@github.com:you/portfolio.git deploy/deploy.sh
#
set -euo pipefail

APP_DIR=${APP_DIR:-/var/www/portfolio}
REPO=${REPO:?Set REPO to the git URL of the repository}
BRANCH=${BRANCH:-main}
KEEP=${KEEP:-5}
PHP=${PHP:-php}

release="$APP_DIR/releases/$(date +%Y%m%d%H%M%S)"
step() { printf '\n\033[1m== %s\033[0m\n' "$1"; }

step "Clone $BRANCH into $release"
git clone --depth 1 --branch "$BRANCH" "$REPO" "$release"
cd "$release"

step "Link shared configuration and storage"
ln -sfn "$APP_DIR/shared/.env" .env
rm -rf storage
ln -sfn "$APP_DIR/shared/storage" storage

step "Install PHP dependencies"
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

step "Build assets (client, SSR bundle, build-meta.json)"
npm ci --no-audit --no-fund
npm run build
# The SSR bundle includes its dependencies (vite.config.ts: ssr.noExternal), so the
# release does not need node_modules at runtime.
rm -rf node_modules

step "Fetch the last CI quality report for the colophon (optional)"
repo_url=$(grep -E '^PORTFOLIO_REPO_URL=' .env | cut -d= -f2- | tr -d '"' || true)
if [ -n "$repo_url" ]; then
    curl -fsSL "$repo_url/releases/download/quality/quality.json" -o storage/app/quality.json \
        || echo "No quality report published yet; the colophon leaves that part out."
fi

step "Migrate and warm caches"
"$PHP" artisan storage:link --force
# Migrations run while the previous release still serves traffic: keep them additive.
"$PHP" artisan migrate --force
"$PHP" artisan optimize
"$PHP" artisan filament:optimize
# Stored note HTML follows renderer changes (e.g. accessibility fixes).
"$PHP" artisan portfolio:render-markdown

step "Switch current to the new release"
ln -sfn "$release" "$APP_DIR/current.next"
mv -Tf "$APP_DIR/current.next" "$APP_DIR/current"

step "Restart workers"
# Queue workers finish their job and restart on the new code (supervisor brings them back).
"$PHP" artisan queue:restart
# Supervisor restarts the SSR server from the new release.
"$PHP" artisan inertia:stop-ssr || true
# PHP-FPM keeps compiled files in opcache: reload it so the new release is served.
if command -v sudo >/dev/null && sudo -n true 2>/dev/null; then
    sudo systemctl reload "php$("$PHP" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm" || true
else
    echo "Reload PHP-FPM now (sudo systemctl reload php8.x-fpm) so opcache serves the new release."
fi

step "Remove old releases (keeping $KEEP)"
ls -1dt "$APP_DIR"/releases/*/ | tail -n +$((KEEP + 1)) | xargs -r rm -rf

step "Deployed $(git -C "$release" rev-parse --short HEAD)"
