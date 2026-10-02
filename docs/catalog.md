# Catalogue module

Location: `app/Modules/Catalog/`. Admin-side only so far (no storefront pages yet).

## Data model

```
categories ──< category_product >── products ──< product_variants ──< stock_movements
 (self-parent)                          │              │
                                        │              └──< variant_attribute_value >── attribute_values >── attributes
                                        └──< product_images
```

| Table | Key points |
|---|---|
| `categories` | Unlimited nesting via `parent_id`; `slug` unique; `is_active`; SEO fields; soft deletes |
| `products` | `status` = `draft` / `active` / `archived`; `published_at`; `is_featured`; SEO fields; soft deletes |
| `category_product` | A product can belong to many categories |
| `attributes` / `attribute_values` | Reusable variant options, e.g. *Colour → Gold, Silver*; `(attribute_id, slug)` unique |
| `product_variants` | Sellable unit: `sku` (unique), prices, stock, flags; soft deletes |
| `variant_attribute_value` | Which option values a variant has |
| `product_images` | `sort_order` (lowest = primary), optional link to one variant |
| `stock_movements` | Append-only stock ledger |

### Money

Prices are **unsigned integers in minor units** (`49900` = ₹499.00). Fields:
`price`, `compare_at_price` (the struck-through "was" price, must be greater than
`price`), `cost_price` (internal). The currency is `config('catalog.currency')`
(`SHOP_CURRENCY`) and is included on every product response. Tax and shipping are
**not** part of the catalogue and will live in the checkout/order layer.

## Business rules

### Variants
- Every product has **at least one variant**, even a simple one. SKU, price and
  stock always live on the variant — cart/order code never special-cases "simple"
  products.
- Exactly **one default variant** per product. Flagging another as default moves
  the flag. If none is flagged on create, the first is the default.
- Deleting the default variant promotes the next one. The **last variant cannot be
  deleted** (422).
- `sku` is unique across the whole catalogue (and within a request).

### Stock
- All changes go through `Actions\AdjustStock`, which runs in a transaction with a
  row lock and writes a `stock_movements` row (`quantity_change`, `quantity_after`,
  `reason`, optional `note`, `user_id`, optional polymorphic `reference`, e.g. an
  order). Nothing else should update `stock_quantity`.
- Stock cannot go **below zero** unless the variant has `allow_backorder`.
- `stock_quantity` on a variant **update** is ignored; use the stock-adjustment
  endpoint. On **create** it records an `initial` movement.
- Manual reasons: `restock`, `correction`, `damaged`, `returned`. `sale` and
  `cancelled` are reserved for the future Orders module.
- `in_stock` = not tracked, or backorder allowed, or quantity > 0.
  `low_stock` = tracked and quantity ≤ `low_stock_threshold`.

### Products
- Creating an `active` product stamps `published_at`; updating a product to `active`
  stamps it on first publication if empty.
- `Product::published()` (scope) = `active` and `published_at` is null or in the
  past. **The storefront must use this scope** so drafts, archived and scheduled
  products are never shown.
- `PUT /products/{id}` updates product fields and `category_ids` only — variants,
  stock and images are managed through their own endpoints.
- Deleting is soft; the product disappears from the API but rows remain.

### Categories
- A category cannot become its own parent or move under a descendant (422).
- A category with sub-categories cannot be deleted (422). Deleting a category is soft: it disappears from product responses
  and listings, and its product links are kept so it can be restored.

### Attributes
- Values are matched by `id`, else by slug, so re-sending a list never duplicates
  values.
- An attribute, or a value, **in use by variants cannot be deleted** (422).

### Images
- Allowed: jpg, jpeg, png, webp, max 5 MB (`config/catalog.php`).
- Files are stored on `CATALOG_IMAGE_DISK` (default `public`) under
  `catalog/<product_id>/` with a server-generated name; the client filename is
  never used. Run `php artisan storage:link`.
- Deleting an image deletes the file. No resizing/WebP conversion yet.

### Slugs
Auto-generated from the name, with `-2`, `-3`… suffixes on collision. Soft-deleted
rows count, so a deleted product's slug is not reused. An explicit `slug` may be
supplied (letters, numbers, dashes, underscores).

## Permissions used

| Area | Permission |
|---|---|
| View products / attributes list | `products.view` |
| Create / update / delete products | `products.create` / `products.update` / `products.delete` |
| Variants, images, attribute changes | `products.update` |
| View / manage categories | `categories.view` / `categories.manage` |
| View stock movements | `inventory.view` |
| Adjust stock | `inventory.manage` |

## Extending

- **Orders:** call `AdjustStock::handle($variant, -$qty, StockReason::Sale, reference: $order)`
  inside the order transaction. A failed check throws a `ValidationException`.
- **Storefront:** query `Product::published()` with `variants`, `images`,
  `categories`; return nothing for drafts.
- **Search:** `Product::search()` currently uses `LIKE` on name and SKU. Move to
  FULLTEXT or a search engine when the catalogue grows.
