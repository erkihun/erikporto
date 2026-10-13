# CLAUDE.md

Portfolio of Erkihun Bewket. Laravel 12 · PHP 8.3 · Inertia v2 + React 19 + TypeScript (SSR) · Tailwind 4 · Filament 4 admin at `/admin` · Pest 3.
All public content lives in the database and is edited in the admin panel. See README.md for setup.

## Checks (run all four before every commit)

```bash
php artisan test
vendor/bin/pint --test        # fix with: vendor/bin/pint
npx tsc --noEmit
npm run build                 # tsc + client bundle + SSR bundle
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

### Caching
- Public page payloads are cached through `App\Support\PortfolioCache::remember()` (versioned keys, works on every cache store).
- Content models use the `FlushesPortfolioCache` trait. Bulk queries that skip model events (e.g. Filament drag-reordering) must call `PortfolioCache::flush()` explicitly.

### Frontend
- The visual design is final. Do not restyle anything. New UI reuses the existing tokens (`--paper`, `--ink`, `--accent`, `--f-*`, …) and classes in `resources/css/app.css`.
- No `any` in TypeScript. No business logic in React components: data comes ready-made from Resources/shared props.
- Pages live in `resources/js/pages`, home sections in `resources/js/components/home`, layout in `components/layout/SiteLayout.tsx`.

### Filament 4
- When unsure how a Filament 4 API works, read the source in `vendor/filament` instead of relying on v3 knowledge.
- Resources are split into `Schemas/`, `Tables/` and `Pages/` folders under `app/Filament/Resources/<Name>/`.

## Working rules
- Ask before adding any Composer or npm package. Verify Laravel 12 / Filament 4 / PHP 8.3 support from the package's composer.json before proposing it.
- One commit per logical change, conventional commit messages (`feat:`, `fix:`, `docs:`, `chore:`, `test:`).
- Tests use Pest with `RefreshDatabase` on in-memory SQLite. Migrations must also work on MySQL.

## Where things live
| Concern | Location |
| --- | --- |
| Routes | `routes/web.php` |
| Home page data | `app/Queries/HomePageQuery.php` |
| Contact workflow | `StoreContactMessageRequest` → `App\Actions\SubmitContactMessage` → queued `NewContactMessage` |
| Seeders | `AdminUserSeeder` always; `DemoContentSeeder` only in local and tests. Production starts empty and `/` renders `ComingSoon` until a profile exists |
| Admin access | `User::canAccessPanel()` requires `is_admin` |
| Ethiopian calendar | `resources/js/lib/ethiopian-calendar.ts` |
