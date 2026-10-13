# CLAUDE.md

Portfolio of Erkihun Bewket. Laravel 12 · PHP 8.3 · Inertia v2 (`@inertiajs/react`) on Preact 11 via preact/compat + TypeScript (SSR) · Tailwind 4 · Filament 4 admin at `/admin` · Pest 3.
All public content lives in the database and is edited in the admin panel. See README.md for setup.

## Checks (run all four before every commit)

```bash
php artisan test
vendor/bin/pint --test        # fix with: vendor/bin/pint
npx tsc --noEmit
npm run build                 # tsc + client bundle + SSR bundle
```

Run `npm run build` before `php artisan test`: feature tests render the Blade root, which needs the Vite manifest entry of every page.

Performance (also in CI):

```bash
npm run budgets               # exact gzip budgets from the build: JS ≤ 100 KB/page, CSS ≤ 20 KB, first-view fonts ≤ 100 KB
npm run perf                  # Lighthouse CI (lighthouserc.cjs) against scripts/perf-server.mjs: HTTP/2 + TLS, SSR, opcache, isolated SQLite
```

## Conventions

### PHP
- `declare(strict_types=1);` in every PHP file. Classes are `final` unless they are designed for extension (e.g. `ProjectSummaryResource`).
- Constructor property promotion; `readonly` classes for value objects and actions.
- Enums for fixed states (`app/Enums`). Stored enum values never change; public labels go through `__()`.
- Thin controllers. Business workflows live in `app/Actions`, read models in `app/Queries`, validation in FormRequests (`app/Http/Requests`), value objects in `app/Data`.
- Mass-assignment protection matters: sensitive columns (`is_admin`, `ip_address`, `read_at`) are never fillable; set them with `forceFill`.

### Inertia contracts
- Every Inertia prop shape comes from an API Resource in `app/Http/Resources` and is mirrored in `resources/js/types/portfolio.ts`. Change both together.
- Shared props are defined in `App\Http\Middleware\HandleInertiaRequests` and typed in `resources/js/types/inertia.d.ts`.

### Languages
- Public pages exist in English (`/`) and Amharic (`/am`). Localized routes are registered twice in `routes/web.php`; build their URLs with `App\Support\LocalizedUrls::route()` (server) or the shared `urls` prop (client), never hard-coded `/`.
- Translatable columns use `HasLocalizedContent` (spatie/laravel-translatable; blank values are dropped so reads fall back to English). Read them in Resources through `App\Support\Localized` (`pick`, `text`, `labelled` for `{value, label}` lists).
- UI strings: add the key to both `lang/en.json` and `lang/am.json` (a test checks parity), then use `useTrans()` in components. New Amharic text is a draft until reviewed: note it as `TODO(review-am)` in the commit or summary.
- Admin forms bind translatable fields as `title.en` / `title.am` through `App\Filament\Support\LocaleTabs`.

### Caching
- Public page payloads are cached through `App\Support\PortfolioCache::remember()` (versioned keys that include the locale, works on every cache store).
- Content models use the `FlushesPortfolioCache` trait. Bulk queries that skip model events (e.g. Filament drag-reordering) must call `PortfolioCache::flush()` explicitly.

### Frontend
- The visual design is final. Do not restyle anything. New UI reuses the existing tokens (`--paper`, `--ink`, `--accent`, `--f-*`, …) and classes in `resources/css/app.css`.
- No `any` in TypeScript. No business logic in components: data comes ready-made from Resources/shared props.
- Components are written against the React API and import from `react`; Vite aliases `react` to `resources/js/lib/preact-react.js` (preact/compat plus an SSR-safe `use()`) and `react-dom/*` to `preact/compat/*`. tsconfig `paths` does the same for types. The SSR build bundles all dependencies (`ssr.noExternal`) so Inertia resolves the alias too. Don't add a package that needs real React internals.
- Fonts are self-hosted, subset woff2 in `resources/fonts/`, declared in the generated `resources/css/fonts.css` (unicode-range splits, metric-matched fallbacks). Never edit those by hand: change `scripts/fonts.mjs` and run `npm run fonts`. A new UI character outside the critical set goes into `CRITICAL_LATIN` (or `ETHIOPIC_CORE_TEXT`) there, or it pulls in an extra file on first view. JetBrains Mono keeps all of ASCII (code blocks in notes) without programming ligatures. First-view fonts budget: 100 KB.
- The production layout inlines the compiled stylesheet (`Vite::content`) and preloads the Archivo critical face; keep CSS under 20 KB gzip.
- First-view font files are preloaded (`App\Support\Fonts::firstView()`). Fallback faces are width-matched per weight/stretch range from `scripts/font-fallbacks.json` (re-measure with `node scripts/measure-font-fallbacks.mjs` after changing fonts), so the swap never shifts layout.
- Initial JS budget is 100 KB gzip. Decorative or on-demand code loads with `import()` (see `whenIdle()` in `lib/idle.ts` and the OrgGraph engine). Inspect the bundle with `npm run analyze` (writes `storage/app/bundle-stats.html`).
- Pages live in `resources/js/pages`, home sections in `resources/js/components/home`, layout in `components/layout/SiteLayout.tsx`.

### Filament 4
- When unsure how a Filament 4 API works, read the source in `vendor/filament` instead of relying on v3 knowledge.
- Resources are split into `Schemas/`, `Tables/` and `Pages/` folders under `app/Filament/Resources/<Name>/`.

## Working rules
- Ask before adding any Composer or npm package. Verify Laravel 12 / Filament 4 / PHP 8.3 support from the package's composer.json before proposing it.
- One commit per logical change, conventional commit messages (`feat:`, `fix:`, `docs:`, `chore:`, `test:`).
- Tests use Pest with `RefreshDatabase` on in-memory SQLite. Migrations must also work on MySQL 8 (the `mysql` CI job migrates, rolls back, migrates again and runs the suite).

## Where things live
| Concern | Location |
| --- | --- |
| Routes | `routes/web.php` |
| Home page data | `app/Queries/HomePageQuery.php` |
| Contact workflow | `StoreContactMessageRequest` → `App\Actions\SubmitContactMessage` → queued `NewContactMessage` |
| Seeders | `AdminUserSeeder` always; `DemoContentSeeder` only in local and tests. Production starts empty and `/` renders `ComingSoon` until a profile exists |
| Admin access | `User::canAccessPanel()` requires `is_admin`; the panel also requires app MFA (Filament `AppAuthentication`, recoverable). Tests that open admin pages over HTTP use `User::factory()->admin()->withMfa()` |
| Ethiopian calendar | `resources/js/lib/ethiopian-calendar.ts` |
| Notes | `Note` model: draft/scheduled/published is derived from `published_at` (never stored). `body_html`, `toc`, `reading_minutes` are derived on save and are not fillable |
| Markdown safety | `MarkdownRenderer`: `html_input=escape`, `allow_unsafe_links=false`. Never render user Markdown any other way |
| Publication state | `HasPublicationDate` concern (Note, NowUpdate): `published()` / `latestFirst()` scopes, derived `status` (`NoteStatus`) and `isLive()` from one `published_at` column |
| Now page | `NowUpdate` rows (`body` Markdown, derived non-fillable `body_html`); `NowController` renders the newest published one plus up to 12 older ones; `hasNow` shared prop drives the footer link |
| Telegram | `AnnouncesOnTelegram` contract + `QueuesTelegramAnnouncement` trait; `AnnounceOnTelegram` job is idempotent (unique job + lock + `telegram_posted_at`) |
| Images | Owners use the `HasImages` concern (`imageAttributes()`, `markdownImageAttributes()`); never delete or resize uploads by hand. `App\Actions\Images\*` do the work, `ResponsiveImages::for()` builds the prop (`ResponsiveImage` in portfolio.ts), `<ResponsiveImg>` renders it with width/height. Admin uploads go through `App\Filament\Support\ImageUpload`. Local tests: `php -d extension=exif vendor/bin/pest` runs the orientation test too |
| Page transitions | `resources/js/lib/view-transitions.ts`: Links use `viewTransition={forward}`; the project cover/title share `project-cover`/`project-title` between card and case study (one card holds them at a time); the back button replays Inertia's popstate inside a transition (never wait on requestAnimationFrame inside the update callback: rendering is paused). CSS in app.css, off under reduced motion |
| Colophon | `ColophonQuery` + `App\Support\BuildMeta` (paths in `config/portfolio.php`); `scripts/build-meta.mjs` runs at the end of `npm run build`, `scripts/quality-report.mjs` in CI. Never hard-code a number on that page |
| Accessibility | `tests/Browser` (Playwright + axe, `npm run test:browser`): add any new page type to `PAGES` in a11y.spec.ts. Markdown code blocks get `tabindex="0"` (FocusableCodeBlockRenderer); run `php artisan portfolio:render-markdown` after renderer changes |
| Command palette | `components/palette/CommandPalette.tsx` (lazy chunk), data from `PaletteController`; scope Playwright locators to the dialog (the contact form has a combobox too) |
| Security headers | `SecurityHeaders` middleware (web group: CSP with `Vite::cspNonce()`; admin: `security:admin` baseline). Any new inline `<script>`/`<style>` in Blade needs `nonce="{{ Vite::cspNonce() }}"`; never add `unsafe-inline` scripts or `unsafe-eval`. A package that injects `<style>` elements at runtime breaks the CSP (see navigation-progress.ts) |
| Error pages | `App\Http\Responses\ErrorPage` (registered in bootstrap/app.php) + `ErrorPageQuery`; shared props that touch the database are wrapped in `rescue()` so the 500/503 page renders during an outage. Keep that true for any new shared prop |
| Scheduled content | `PortfolioCache` TTL is capped at the next scheduled note or /now update (computed on a cache miss), so pages refresh on time without a cron |
