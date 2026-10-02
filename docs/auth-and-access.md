# Authentication & access control

## Admin authentication

Cookie/session auth via **Laravel Sanctum's SPA mode**. No tokens are stored in the
browser. The admin SPA (`FRONTEND_ADMIN_URL`) must be listed in
`SANCTUM_STATEFUL_DOMAINS`.

### Flow

1. `GET /sanctum/csrf-cookie` — sets the `XSRF-TOKEN` cookie.
2. `POST /api/v1/admin/auth/login` with `{ "email", "password" }`, sending the
   `X-XSRF-TOKEN` header (axios does this automatically with `withCredentials: true`
   and `withXSRFToken: true`).
3. Subsequent requests carry the session cookie.
4. `POST /api/v1/admin/auth/logout` ends the session.

Requests without a valid CSRF token return **419**.

### Security measures

| Measure | Detail |
|---|---|
| Session fixation | Session ID regenerated on login |
| Logout | Session invalidated, CSRF token rotated |
| CSRF | Enforced by Sanctum for stateful requests |
| Rate limiting | 5 failed attempts per **normalized email + IP** (lower-cased, trimmed), then HTTP 429 with a retry time. Success clears the counter |
| Account enumeration | Unknown email, wrong password and a *customer* trying admin login all return the identical generic error |
| Customers | Can never log in to the admin, even with correct credentials |

Implementation: `app/Http/Controllers/Api/V1/Admin/AuthController.php`,
`app/Http/Requests/Admin/AdminLoginRequest.php`.

### Responses

Login and `GET /api/v1/admin/auth/me` return:

```json
{
  "user": { "id": 1, "name": "…", "email": "…" },
  "roles": ["Product Manager"],
  "is_super_admin": false,
  "permissions": ["products.view", "products.create", "…"]
}
```

The admin UI should use `permissions` to show or hide menu items.

## Authorization

### Model

- Customers and staff are rows in the same `users` table.
- A user is **staff** if they hold at least one of the six admin roles
  (`User::isAdmin()`). A customer holds none.
- `EnsureUserIsAdmin` (alias `admin`) blocks non-staff from admin routes (403).
  A customer who is directly given a permission is still **not** admin.

### Middleware stack for admin routes

`auth:sanctum` → `admin` → `permission:<name>`

### Roles

Defined in [app/Support/Access/Role.php](../app/Support/Access/Role.php).

| Role | Responsibility |
|---|---|
| Super Admin | Everything. Bypasses all checks (`Gate::before`) |
| Admin | All permissions **except** `roles.manage` and `admins.manage` |
| Product Manager | Products, categories, inventory |
| Order Manager | Orders, fulfilment, shipping, view inventory/customers |
| Content Manager | Banners, pages, blogs; view products/categories |
| Customer Support | Customers, orders (view/update), review moderation, view shipping |

### Permissions

Defined in [app/Support/Access/Permission.php](../app/Support/Access/Permission.php),
named `<module>.<action>`: `products.{view,create,update,delete}`,
`categories.{view,manage}`, `inventory.{view,manage}`,
`orders.{view,update,fulfil,refund,cancel}`, `shipping.{view,manage}`,
`customers.{view,update}`, `coupons.{view,manage}`, `reviews.moderate`,
`banners.manage`, `pages.manage`, `blogs.manage`, `reports.view`,
`settings.{view,manage}`, `admins.{view,manage}`, `roles.manage`.

### Changing roles or permissions

1. Edit the enums (add a permission case, or change a role's `permissions()` map).
2. Run `php artisan db:seed --class=RolesAndPermissionsSeeder`.

The seeder is idempotent: it creates missing rows, syncs each role, and removes
permissions no longer in the enum. Using a permission in code before adding it to
the enum will fail the tests.

### First Super Admin

Set `ADMIN_EMAIL` / `ADMIN_PASSWORD` (and optionally `ADMIN_NAME`) in `.env`, then
run `php artisan db:seed`. Nothing is hardcoded.

## Known gaps

- No admin password reset and no 2FA yet — add both before launch.
- Storefront and admin share the `web` guard/session on the Laravel side. In
  production, serve the admin from its own subdomain with a separate session cookie
  name so a customer session and a staff session cannot interact.
- No user management endpoints (create/edit staff, assign roles) yet; permission
  keys `admins.*` and `roles.manage` are reserved for them.
