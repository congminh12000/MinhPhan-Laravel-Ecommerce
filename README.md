# MinhPhan Commerce

MinhPhan Commerce is a customized, production-ready ecommerce build implemented on top of Laravel. This repository packages the exact storefront, admin configuration, and localized catalog experience that is currently being deployed and operated by MinhPhan.

This build focuses on practical delivery rather than framework experimentation: Vietnamese-first merchandising, curated seed data, payment and shipping integrations, Railway deployment, and operational fixes needed to run a real store.

## What Was Customized

- Rebranded public-facing storefront and admin UI to `MinhPhan`
- Localized storefront and admin content to Vietnamese, including catalog, categories, attributes, article categories, and plugin labels
- Added richer product descriptions and translated multi-spec content in the database snapshot
- Integrated SePay payment flow with callback aliases, stricter logging, and webhook debugging support
- Hardened deployment bootstrap for Railway with environment preflight checks
- Standardized snapshot seeding so a fresh environment can boot with the current default catalog and content
- Fixed admin locale issues that broke save actions in Vietnamese
- Fixed settings edge cases such as empty required values blocking configuration saves

## Current Stack

- PHP 8.2+
- Laravel 10
- Blade + Vue + jQuery
- MySQL
- Bootstrap
- Railway deployment
- SePay payment integration

## Production-Oriented Capabilities

- Multi-language storefront with Vietnamese and English content
- Multi-currency and shipping method support
- Admin-ready product, category, page, attribute, and plugin management
- Payment callback processing with structured logs for debugging
- Seed snapshot workflow for repeatable environment bootstrap
- Docker-based deployment path for Railway

## Local Development

1. Install dependencies:

```bash
composer install
npm install
```

2. Prepare the environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure MySQL in `.env`, then run:

```bash
php artisan migrate --seed
```

4. Build frontend assets:

```bash
npm run build
```

5. Start the app using your preferred local web server or:

```bash
php artisan serve
```

## Deployment Notes

- Production is deployed on Railway
- Runtime expects MySQL configuration via environment variables
- `APP_KEY` must be present before boot
- Seed snapshot is intended for fresh environments or controlled resets, not for overwriting live transactional data

## Snapshot Seeding

The repository includes a local snapshot seeder that restores the current default catalog, content, and configuration used by this custom build.

Key purpose:

- keep new environments visually aligned with the current localhost baseline
- preserve translated demo content and default storefront settings
- reduce manual setup after deployment

Operational caution:

- do not run snapshot restoration blindly on an already active production database with live orders

## Main Customization Areas

- Storefront branding and localized content
- Admin localization and usability fixes
- Product data enrichment
- SePay webhook handling
- Railway deployment hardening
- Seed/data portability for new environments

## Suggested Demo Talking Points

If this repository is used in a portfolio or technical walkthrough, the most relevant implementation areas are:

- deployment troubleshooting across local and Railway environments
- payment callback debugging and idempotent order updates
- multilingual data cleanup and translation normalization
- seed strategy for consistent default data across environments
- admin usability fixes discovered through real QA flows

## Attribution And License

This repository is a customized build based on BeikeShop. Original upstream licensing, copyright notices, and third-party attributions remain applicable.

See:

- [LICENSE](LICENSE)
- [README-zh-CN.md](README-zh-CN.md)

If you distribute or commercialize this codebase, review the upstream license obligations carefully before removing or changing any required notices.
