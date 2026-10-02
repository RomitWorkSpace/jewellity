# Architecture

## Overview

```
 Browser (customer) ──► Laravel Blade + Alpine   (server-rendered storefront, routes/web.php)
 Browser (staff)    ──► React admin SPA ──► REST API  (routes/api.php, /api/v1/admin/*)
                                   both ──► Laravel services/models ──► MySQL
```

One Laravel application serves both the storefront and the API. The admin is a
separate React app that talks to the API only.

### Why this shape

- **Storefront in Blade + Alpine:** product and category pages are rendered on the
  server, so search engines and social-media crawlers get complete HTML with no
  Node server to host. Alpine handles small interactions only (menus, mini-cart,
  galleries). Persistent state (cart, wishlist, auth) lives in Laravel.
- **Admin as a React SPA:** it is a data-heavy internal tool where SEO is irrelevant,
  so a client-side app with TanStack Query is the best fit.
- **Business logic is not in controllers.** It lives in *Actions* and models inside
  modules, so the storefront controllers and API controllers can share it.

## Repository layout

```
app/
  Http/                    Cross-cutting HTTP code (auth controllers, middleware, requests)
  Models/User.php          Customers and staff share this model
  Modules/
    Catalog/               First feature module (see docs/catalog.md)
  Support/Access/          Role + Permission enums (single source of truth)
bootstrap/providers.php    Registers module service providers
config/catalog.php         Currency and image settings
database/factories/        Model factories (Catalog/ subfolder per module)
docs/                      This documentation
frontend/                  npm workspace for the admin SPA (apps/admin)
resources/                 Storefront views, CSS, JS (Alpine)
routes/api.php             Public/auth API routes; modules add their own
routes/web.php             Storefront routes
tests/Feature/             Feature tests (Admin/, Catalog/)
```

### Module convention

Each module in `app/Modules/<Name>/` is self-contained:

```
<Name>ServiceProvider.php   loads migrations + routes
routes.php                  mounted under /api/v1/admin with auth:sanctum + admin
Models/  Enums/  Concerns/  Actions/
Http/Controllers/Admin/  Http/Requests/Admin/  Http/Resources/
database/migrations/
```

To add a module: create the folder, add its service provider to
`bootstrap/providers.php`, and follow the same layout. Orders, Customers and
Content should follow this.

## Conventions

- **API versioning:** all routes are under `/api/v1`. Breaking changes get `/v2`.
- **Money** is always an **integer in minor units** (paise/cents). Never floats.
  The currency comes from `config('catalog.currency')`.
- **Permissions, not roles, guard code.** Routes/controllers use
  `permission:products.view`; never check role names.
- **Form Requests** validate input; **API Resources** shape output.
- **State-changing business operations are Actions** (`AdjustStock`, `CreateProduct`,
  `SaveVariant`) — one class, one job, wrapped in a transaction when needed.
- **Soft deletes** on products, variants and categories so history stays intact.
- **Authorization is enforced server-side.** The admin UI hiding a button is a
  convenience, never a control.
- **Slugs** are generated automatically and unique (soft-deleted rows included).

## Frontend

- `frontend/` is an npm workspace. Today it holds only `apps/admin`
  (React 19, JavaScript/JSX, Vite, Tailwind v4, TanStack Query, axios,
  react-router-dom). The `@/` alias maps to `src/`. It currently shows a
  placeholder page that pings the API.
- The storefront's Vite/Tailwind/Alpine build is at the repo root
  (`vite.config.js`, `resources/css/app.css`, `resources/js/`).
  `resources/js/stores.js` holds Alpine stores for page-level UI state only.

## SEO (storefront)

`<x-seo>` ([resources/views/components/seo.blade.php](../resources/views/components/seo.blade.php))
renders title, description, canonical URL, Open Graph / Twitter tags and an optional
JSON-LD block. Wrap pages in `<x-layouts.storefront>` and pass the component via
the `seo` slot. Sitemap and robots.txt are not yet implemented.

## Infrastructure notes

- Sessions, cache and queues use the **database** driver for now. Switching to Redis
  later is an `.env` change plus a Redis client (`predis/predis` or `phpredis`).
- Tests run against MySQL (`jewellity_testing`) because this environment's PHP has
  no SQLite driver.
- `SESSION_DOMAIN=localhost` is for local development only.
