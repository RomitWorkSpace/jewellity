# Roadmap

## Done

- Project setup: Laravel 12, Sanctum, Tailwind v4, Vite, Alpine, React 19 admin
  workspace. Redis deliberately deferred (database drivers in use).
- Admin roles & permissions (Spatie) — six roles, 29 permissions.
- Admin authentication — session login/logout, CSRF, rate limiting, access control.
- Catalogue foundation — categories, products, variants, attributes, images, stock
  ledger (admin API, 54 passing tests).
- Storefront shell — Blade layout and SEO component, placeholder home page.

## Next (suggested order)

1. Storefront catalogue pages: home, category listing with filters, product page
   (with JSON-LD, sitemap, robots.txt).
2. Admin React screens: login, layout driven by `permissions`, category/product/
   stock management.
3. Customer accounts, cart (session/DB), addresses.
4. Checkout: tax, shipping, coupons, payment gateway.
5. Orders module: management, fulfilment, stock integration (`AdjustStock`).
6. Queued notifications (email/SMS); move queues, cache and sessions to Redis.
7. Reviews, wishlist, reports.

## Before launch

- Admin password reset and 2FA.
- Admin on its own subdomain with a separate session cookie.
- Staff/user management endpoints.
- OpenAPI documentation generated from the routes (e.g. Scribe).
- Image resizing / WebP conversion and CDN.
- Production env: `SESSION_DOMAIN`, HTTPS, `APP_DEBUG=false`, caching of
  config/routes, backups, monitoring.
