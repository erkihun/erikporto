# Erkihun Bewket — Portfolio

Laravel 12 · Inertia 2 (Preact 11 via preact/compat + TypeScript, SSR) · Tailwind 4 · Filament 4 admin · Pest.

All public content lives in the database and is edited at **/admin**: profile, projects, capabilities, process, experience and education. Only the section headings are hard-coded.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# set PORTFOLIO_ADMIN_EMAIL and PORTFOLIO_ADMIN_PASSWORD in .env
touch database/database.sqlite   # or configure MySQL in .env
php artisan migrate --seed       # admin user always; demo content only when APP_ENV=local
php artisan storage:link
npm run build                    # type-check, client bundle, SSR bundle
```

Local development:

```bash
php artisan serve
npm run dev
php artisan queue:work           # sends contact-form notification emails
```

Open http://127.0.0.1:8000 and http://127.0.0.1:8000/admin.

## Architecture

| Concern | Where |
| --- | --- |
| Routes | `routes/web.php`: `/`, `/work/{slug}` and `POST /contact` in English, the same under `/am` in Amharic; `/notes`, `/notes/{slug}`, `/feed.xml`, `/now`, `/cv`, `/sitemap.xml` in one language |
| Languages | English (default) and Amharic. Content columns are `{"en": …, "am": …}` JSON (spatie/laravel-translatable via `HasLocalizedContent`); the admin forms have an English tab (required) and an አማርኛ tab (optional, blank falls back to English). UI strings live in `lang/en.json` and `lang/am.json` (same keys, enforced by a test). Notes and /now are written in one language |
| Notes | `App\Models\Note` (state derived from `published_at`), Markdown rendered safely on save by `App\Support\Markdown\MarkdownRenderer` |
| Telegram | `AnnounceOnTelegram` job (idempotent) + `portfolio:announce` every 5 minutes; disabled until `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHANNEL_ID` are set |
| Now page | `/now` shows the newest published `App\Models\NowUpdate` (Markdown via the same `MarkdownRenderer`); older updates collapse below as history. Edited in **Admin → Content → Now**; not announced on Telegram |
| Images | Uploads (JPEG/PNG/WebP, max 4 MB, no SVG) are registered on save with their size (`image_assets`), then the queued `ProcessUploadedImage` job rotates them upright, rewrites them without EXIF and writes WebP variants at 640/1280/1920 px. Pages get `{url, srcset, width, height}`; Markdown images get the same attributes. Replaced or removed images are deleted with their variants; `portfolio:images` (daily) processes leftovers and deletes orphaned uploads |
| Page data | `app/Queries/HomePageQuery.php` and `ProjectController`, both cached |
| API contracts | `app/Http/Resources/*` ⇄ `resources/js/types/portfolio.ts` |
| Contact workflow | `StoreContactMessageRequest` → `SubmitContactMessage` action → queued `NewContactMessage` notification |
| Admin | `app/Filament/Resources/*`, open only to users with `is_admin = true` |
| Cache | `App\Support\PortfolioCache`: versioned keys, flushed when any content model is saved or deleted and after drag-reordering |
| Frontend | `resources/js/pages` (Inertia pages), `components/home` (sections), `components/home/OrgGraph.tsx` + `lib/org-graph-engine.ts` (hero motion canvas, loaded when idle) |
| React API | Preact via preact/compat: aliases in `vite.config.ts`, compat module `resources/js/lib/preact-react.js`. `npm run analyze` shows the bundle treemap |
| Fonts | Self-hosted subset woff2 in `resources/fonts`, generated with `@font-face` rules into `resources/css/fonts.css` by `npm run fonts` (`scripts/fonts.mjs`). First view loads only the critical Latin and Ethiopic-core files. OFL licences in `resources/fonts/licenses` |
| Ethiopian calendar | `resources/js/lib/ethiopian-calendar.ts` |

### Security
- Contact form: FormRequest validation, a honeypot field (bots receive a fake success), and per-IP rate limits (3 per minute and 10 per day by default). `ip_address` and `read_at` can never be mass-assigned.
- The CV is stored on the private `local` disk and served only through `/cv`.
- The admin panel requires `is_admin`. That flag isn't fillable; only the seeder sets it.
- Admins must use an authenticator app (TOTP) with recovery codes: on first sign-in the panel redirects to `/admin/multi-factor-authentication/set-up` and nothing else opens until it is done. Codes and recovery codes are managed at `/admin/profile`. The secret and the hashed recovery codes are stored encrypted with `APP_KEY` (rotating `APP_KEY` means re-enrolling).
- Sign-in is limited to 5 attempts per minute per IP, and code entry is rate-limited per account. Sessions live in the database, are regenerated on login, and in production should be encrypted and HTTPS-only (`SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_LIFETIME` minutes of idle time, `SESSION_SAME_SITE=lax`).
- `Model::shouldBeStrict()` is on outside production, and HTTPS is forced in production.

## Production checklist
- Production seeds only the admin user (`php artisan db:seed --force`). The home page shows a "coming soon" page until the profile is filled in at /admin.
- `APP_ENV=production`, `APP_DEBUG=false` and the real `APP_URL`
- `SESSION_ENCRYPT=true` and `SESSION_SECURE_COOKIE=true`
- First admin sign-in: scan the QR code with an authenticator app and store the recovery codes somewhere safe
- MySQL 8 configured in `.env` (the migrations work on SQLite and MySQL; CI runs the suite on both)
- Amharic UI strings in `lang/am.json` and the demo content are drafts: have a native speaker review them (search for `TODO(review-am)`)
- A mail driver, plus a queue worker under Supervisor (it also processes uploaded images)
- PHP with `gd` (WebP support) and `exif` (upright phone photos), and `php artisan storage:link` once
- The scheduler cron (`* * * * * php artisan schedule:run`) so scheduled notes are announced on Telegram
- Telegram (optional): create a bot with @BotFather, add it as an admin of your channel, set `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHANNEL_ID`
- `php artisan inertia:start-ssr` under Supervisor
- `php artisan optimize` and `php artisan filament:optimize` on each deploy
- Real screenshots uploaded in **Admin → Projects → Media** to replace the illustrated placeholders

## Performance

Budgets (mobile): initial JS ≤ 100 KB gzip per page, CSS ≤ 20 KB gzip (inlined), first-view fonts ≤ 100 KB, CLS ≤ 0.02, TBT ≤ 100 ms, Accessibility / Best Practices / SEO 100.

```bash
npm run build && npm run budgets   # exact byte budgets from the production build
npm run perf                       # Lighthouse CI: 4 pages × 3 runs, simulated Slow 4G
```

`npm run perf` starts `scripts/perf-server.mjs`: a fresh SQLite database seeded by `LighthouseSeeder`, isolated config/route/view caches, PHP with opcache and gzip, the SSR server, and an HTTP/2 + TLS front (production serves HTTP/2; Lighthouse's network model treats HTTP/1.1 very differently). The CI `lighthouse` job runs the same thing and keeps the reports as an artifact.

## Tests

```bash
php artisan test
```
