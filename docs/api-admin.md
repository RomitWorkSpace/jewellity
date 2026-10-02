# Admin API reference

Base URL: `{APP_URL}/api/v1/admin`. JSON in and out (send `Accept: application/json`).
All endpoints except login require an authenticated staff session
(see [auth](auth-and-access.md)) and the permission listed.

## Conventions

- **Auth:** cookie session + CSRF (`GET /sanctum/csrf-cookie`, then send
  `X-XSRF-TOKEN`).
- **Responses:** single resources are wrapped as `{ "data": { … } }`; lists as
  `{ "data": [ … ], "links": …, "meta": … }` when paginated.
- **Money:** integers in minor units.
- **Errors**

| Status | Meaning |
|---|---|
| 401 | Not logged in |
| 403 | Logged in but not staff, or missing the permission |
| 404 | Not found (also: a variant/image that belongs to a different product) |
| 419 | Missing/invalid CSRF token |
| 422 | Validation failed: `{ "message": "…", "errors": { "field": ["…"] } }` |
| 429 | Login rate-limited |

## Authentication

| Method | Path | Permission | Notes |
|---|---|---|---|
| POST | `/auth/login` | public | `{ email, password }` → user, roles, permissions |
| POST | `/auth/logout` | staff | 204 |
| GET | `/auth/me` | staff | Current user, roles, `is_super_admin`, permissions |

Health check: `GET /api/v1/ping` → `{ "status": "ok" }` (public).

## Categories

| Method | Path | Permission |
|---|---|---|
| GET | `/categories` | `categories.view` |
| GET | `/categories/{id}` | `categories.view` |
| POST | `/categories` | `categories.manage` |
| PUT/PATCH | `/categories/{id}` | `categories.manage` |
| DELETE | `/categories/{id}` | `categories.manage` |

`GET /categories` returns a flat list ordered by `sort_order`, `name`
(`?active_only=1` filters). Build the tree client-side from `parent_id`.

Body: `name` (required), `slug`, `parent_id`, `description`, `sort_order`,
`is_active`, `meta_title`, `meta_description`.

## Attributes

| Method | Path | Permission |
|---|---|---|
| GET | `/attributes` | `products.view` |
| POST | `/attributes` | `products.update` |
| PUT/PATCH | `/attributes/{id}` | `products.update` |
| DELETE | `/attributes/{id}` | `products.update` |

```json
{ "name": "Colour", "values": [ { "value": "Gold" }, { "id": 3, "value": "Silver", "sort_order": 1 } ] }
```

## Products

| Method | Path | Permission |
|---|---|---|
| GET | `/products` | `products.view` |
| GET | `/products/{id}` | `products.view` |
| POST | `/products` | `products.create` |
| PUT/PATCH | `/products/{id}` | `products.update` |
| DELETE | `/products/{id}` | `products.delete` (soft delete) |

**List filters:** `q` (name or SKU), `status` (`draft|active|archived`),
`category_id`, `per_page` (1–100, default 20), `page`.

**Create** (`POST /products`):

```json
{
  "name": "Pearl Drop Earrings",
  "status": "active",
  "short_description": "…",
  "description": "…",
  "is_featured": false,
  "category_ids": [1, 4],
  "variants": [
    {
      "sku": "PDE-001",
      "price": 49900,
      "compare_at_price": 69900,
      "stock_quantity": 12,
      "attribute_value_ids": [2],
      "is_default": true
    }
  ]
}
```

`variants` is required (1–100). Variant fields: `sku` (required, unique),
`price` (required), `compare_at_price`, `cost_price`, `barcode`, `weight_grams`,
`stock_quantity` (initial), `low_stock_threshold`, `track_inventory`,
`allow_backorder`, `is_default`, `is_active`, `attribute_value_ids`.

**Update** accepts only product fields and `category_ids`; any `variants` sent are
ignored.

**Product response**

```json
{
  "data": {
    "id": 1, "name": "…", "slug": "pearl-drop-earrings", "status": "active",
    "currency": "INR", "published_at": "…",
    "categories": [ { "id": 1, "name": "Earrings", "…": "…" } ],
    "variants": [
      {
        "id": 1, "sku": "PDE-001", "price": 49900, "compare_at_price": 69900,
        "stock_quantity": 12, "in_stock": true, "low_stock": false,
        "is_default": true, "is_active": true,
        "attributes": [ { "attribute": "Colour", "value_id": 2, "value": "Gold" } ]
      }
    ],
    "images": [ { "id": 1, "url": "…", "alt": null, "sort_order": 1 } ]
  }
}
```

## Variants

Permission: `products.update`.

| Method | Path |
|---|---|
| POST | `/products/{product}/variants` |
| PUT | `/products/{product}/variants/{variant}` |
| DELETE | `/products/{product}/variants/{variant}` |

Same fields as above. `stock_quantity` is honoured on create only.

## Images

Permission: `products.update`.

| Method | Path | Notes |
|---|---|---|
| POST | `/products/{product}/images` | `multipart/form-data`: `image` (required), `alt`, `product_variant_id` |
| PUT | `/products/{product}/images/order` | `{ "order": [imageId, …] }` — first = primary |
| DELETE | `/products/{product}/images/{image}` | Also deletes the file |

## Stock

| Method | Path | Permission |
|---|---|---|
| GET | `/variants/{variant}/stock-movements` | `inventory.view` (paginated, newest first) |
| POST | `/variants/{variant}/stock-adjustments` | `inventory.manage` |

```json
{ "quantity_change": -3, "reason": "damaged", "note": "Broken clasp" }
```

`quantity_change` is a non-zero integer (negative removes stock). `reason` is one of
`restock|correction|damaged|returned`. Returns the updated variant (201). Removing
more than is available returns 422 unless the variant allows backorder.
