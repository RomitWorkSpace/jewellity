<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Concerns\HasUniqueSlug;
use App\Modules\Catalog\Enums\ProductStatus;
use Database\Factories\Catalog\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    use HasFactory, HasUniqueSlug, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'status', 'is_featured',
        'meta_title', 'meta_description', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Visible on the storefront: active and already published. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** Products with at least one active variant (otherwise there is nothing to buy). */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->whereHas('variants', fn ($v) => $v->where('is_active', true));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn ($q) => $q
            ->where('name', 'like', $like)
            ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $like)));
    }

    /** Lowest active variant price in minor units, or null. Needs `variants` loaded. */
    public function minPrice(): ?int
    {
        return $this->variants->where('is_active', true)->min('price');
    }

    public function maxPrice(): ?int
    {
        return $this->variants->where('is_active', true)->max('price');
    }

    /** The variant whose price is shown by default (the default one if active, else cheapest). */
    public function displayVariant(): ?ProductVariant
    {
        $active = $this->variants->where('is_active', true);

        return $active->firstWhere('is_default', true) ?? $active->sortBy('price')->first();
    }

    public function isSoldOut(): bool
    {
        $active = $this->variants->where('is_active', true);

        return $active->isEmpty() || $active->every(fn (ProductVariant $v) => ! $v->isInStock());
    }

    /** Percentage off for the display variant when a higher compare-at price is set. */
    public function discountPercent(): ?int
    {
        $v = $this->displayVariant();

        if (! $v || ! $v->compare_at_price || $v->compare_at_price <= $v->price) {
            return null;
        }

        return (int) round((1 - $v->price / $v->compare_at_price) * 100);
    }
}
