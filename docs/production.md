# Running the portfolio in production

A step-by-step guide for a single Linux server (Ubuntu/Debian) with nginx, PHP-FPM, MySQL 8,
supervisor and cron. Everything here has a ready file in `deploy/`.

## 1. Server requirements

| Need | Why |
| --- | --- |
| PHP 8.3 or newer with `intl`, `mbstring`, `pdo_mysql`, `gd` (built with WebP), `exif`, `zip`, `opcache` | Laravel 12, the image pipeline (WebP variants, upright phone photos), backups |
| MySQL 8 (or MariaDB 10.6+) and the `mysqldump` client | Content, sessions, cache, queue; nightly backups |
| Node.js 22 and npm | Building assets on each deploy (the running site does not use Node except for SSR) |
| Composer 2, git, curl | Deploys |
| nginx with HTTP/2 (and `ngx_brotli` if available) | `deploy/nginx.conf` |
| supervisor | Queue worker and the SSR server |

Check GD's WebP support with `php -r 'var_dump(gd_info()["WebP Support"]);'`.

## 2. First-time setup

```bash
sudo mkdir -p /var/www/portfolio/{releases,shared}
sudo chown -R www-data:www-data /var/www/portfolio
sudo -u www-data -s

# Shared storage: the layout Laravel expects, kept across releases.
git clone --depth 1 <repo> /tmp/portfolio-skeleton
cp -r /tmp/portfolio-skeleton/storage /var/www/portfolio/shared/storage
cp /tmp/portfolio-skeleton/.env.example /var/www/portfolio/shared/.env
rm -rf /tmp/portfolio-skeleton
```

Edit `/var/www/portfolio/shared/.env` (section 3), then generate the key once and **store a
copy of `APP_KEY` in a password manager**: it encrypts sessions and the admins' MFA secrets,
and it is not part of the backups.

```bash
cd /var/www/portfolio/shared && php -r 'echo "APP_KEY=base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

Deploy (section 5), then create the admin account once:

```bash
cd /var/www/portfolio/current && php artisan db:seed --force   # admin user only in production
```

Then:

1. nginx: copy `deploy/nginx.conf` to `/etc/nginx/sites-available/portfolio`, replace the lines
   marked `CHANGE`, enable it, get a certificate (`certbot --nginx -d example.com`), `nginx -t`,
   reload.
2. supervisor: copy `deploy/supervisor/*.conf` to `/etc/supervisor/conf.d/`, then
   `supervisorctl reread && supervisorctl update`.
3. cron: `crontab -u www-data deploy/crontab` (the Laravel scheduler, every minute).
4. Sign in at `/admin` with `PORTFOLIO_ADMIN_EMAIL` / `PORTFOLIO_ADMIN_PASSWORD`. The panel asks
   you to scan a QR code with an authenticator app and shows recovery codes: store them with
   `APP_KEY`. Change the password afterwards on the profile page (`/admin/profile`) and remove
   `PORTFOLIO_ADMIN_PASSWORD` from `.env`.
5. Fill in the profile; the public site shows "coming soon" until it exists.

## 3. Environment

Start from `.env.example`. Production values:

| Variable | Value |
| --- | --- |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | `https://example.com` (used for links, the sitemap, feeds and image URLs) |
| `APP_KEY` | generated once (section 2) |
| `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL |
| `SESSION_DRIVER` | `database` |
| `SESSION_ENCRYPT` | `true` |
| `SESSION_SECURE_COOKIE` | `true` (cookies only over HTTPS) |
| `SESSION_LIFETIME` | minutes of inactivity before an admin is signed out (default 120) |
| `SESSION_SAME_SITE` | `lax` |
| `CACHE_STORE`, `QUEUE_CONNECTION` | `database` (or `redis`) |
| `MAIL_*` | a real mailer: contact notifications and backup failures |
| `PORTFOLIO_ADMIN_EMAIL`, `PORTFOLIO_ADMIN_PASSWORD` | first admin (seeded once) |
| `PORTFOLIO_NOTIFY_EMAIL` | where contact messages go (defaults to the profile email) |
| `PORTFOLIO_REPO_URL` | public repository URL: the colophon links the deployed commit, and deploys fetch the CI quality report |
| `BACKUP_DISK` | a disk on another machine, e.g. `s3` with the `AWS_*` variables (any S3-compatible storage) |
| `BACKUP_NOTIFICATION_EMAIL` | who is told when a backup fails or gets too old |
| `BACKUP_ARCHIVE_PASSWORD` | encrypts the backup archives (AES-256); store it with `APP_KEY` |
| `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHANNEL_ID` | optional: announce notes and projects (see README) |
| `INERTIA_SSR_URL` | only if the SSR server does not listen on `http://127.0.0.1:13714` |

## 4. What runs where

| Process | Started by | Does |
| --- | --- | --- |
| PHP-FPM + nginx | systemd | serves the site; static `/build` files are cached for a year (hashed names) |
| `portfolio-queue` | supervisor | contact notification mail, Telegram posts, image processing (EXIF strip, WebP variants) |
| `portfolio-ssr` | supervisor | server-side rendering (pages still work client-side if it is down) |
| `schedule:run` | cron, every minute | Telegram announcements (5 min), backups (01:00 clean, 01:30 run, 09:00 health check), image housekeeping (03:30) |

## 5. Deploying

```bash
sudo -u www-data REPO=git@github.com:you/portfolio.git /var/www/portfolio/current/deploy/deploy.sh
```

(For the very first deploy, run the script from a checkout.) It builds the new release beside the
live one: dependencies, assets, migrations, caches, then switches `current` atomically and
restarts the queue worker and the SSR server. If any step fails, the live site is untouched.
Migrations run while the old release still serves, so keep them additive (add columns first,
drop them in a later release).

**Rolling back:** point `current` at the previous release and restart the workers:

```bash
cd /var/www/portfolio
ln -sfn releases/<previous> current.next && mv -Tf current.next current
php current/artisan queue:restart && php current/artisan inertia:stop-ssr
```

(If the release you are leaving ran a migration, run `php artisan migrate:rollback` from it first.)

**Maintenance window:** `php artisan down` shows the designed 503 page; `php artisan up` ends it.

## 6. Backups and restoring

Every night `backup:run` stores one zip on `BACKUP_DISK` with the database dump and
`storage/app/public` + `storage/app/private` (uploaded images with their variants, and the CV).
`backup:clean` keeps every backup from the last day, one per day for 7 days and one per week
for 4 weeks. `backup:monitor` mails `BACKUP_NOTIFICATION_EMAIL` when the newest backup is
older than a day or the backups pass 5 GB; a failed backup mails immediately. Successful nights
are silent.

Check them any time with `php artisan backup:list`.

**Restore** (tested procedure):

```bash
php artisan down
# 1. Download the archive from the backup disk and unzip it (password: BACKUP_ARCHIVE_PASSWORD).
unzip portfolio-backup.zip -d /tmp/restore
# 2. Database: load the dump into an empty database.
mysql -u <user> -p <database> < /tmp/restore/db-dumps/mysql-<database>.sql
# 3. Files: the archive paths start at storage/, matching the shared directory.
rsync -a /tmp/restore/storage/app/ /var/www/portfolio/shared/storage/app/
php artisan storage:link --force
# 4. Same APP_KEY as before (from the password manager), then:
php artisan optimize:clear && php artisan optimize
php artisan up
```

Admins keep their authenticator apps only if `APP_KEY` is the original one; otherwise clear
`app_authentication_secret` / `app_authentication_recovery_codes` for the user and they set
MFA up again at the next sign-in.

## 7. Security notes

- Public pages send a strict Content Security Policy with a per-request nonce, plus `nosniff`,
  a strict referrer policy, a Permissions-Policy and HSTS (README, Security). Test changes with
  `npm run test:browser`: it fails on any CSP violation.
- HSTS is sent with `max-age` one year and `includeSubDomains`. Only add `preload` (and submit
  the domain to hstspreload.org) once every subdomain serves HTTPS.
- The admin panel requires an authenticator app; sign-in is limited to 5 attempts per minute
  per IP. Keep `APP_DEBUG=false`: debug pages show configuration.
- Uploads are limited to JPEG/PNG/WebP up to 4 MB; SVG is refused. Images are rewritten without
  metadata (GPS, camera) before they are public.

## 8. Performance

- `/build/*` and `/storage/*` are cached by browsers; HTML is not.
- Brotli (or gzip) is on for text assets in `deploy/nginx.conf`. The stylesheet is inlined in
  the HTML, so the first view makes no render-blocking request.
- PHP: enable opcache (`opcache.enable=1`, `opcache.validate_timestamps=0` is safe because
  deploys reload PHP-FPM).
- Budgets and Lighthouse targets: README, Performance. `npm run perf` runs Lighthouse CI against
  a production-like local server.
