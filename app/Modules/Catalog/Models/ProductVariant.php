<?php

namespace App\Modules\Catalog\Models;

use Database\Factories\Catalog\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(ProductVariantFactory::class)]
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'price', 'compare_at_price', 'cost_price',
        'weight_grams', 'stock_quantity', 'low_stock_threshold', 'track_inventory',
        'allow_backorder', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'cost_price' => 'integer',
            'weight_grams' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'track_inventory' => 'boolean',
            'allow_backorder' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_value');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    public function isInStock(): bool
    {
        return ! $this->track_inventory || $this->allow_backorder || $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory && $this->stock_quantity <= $this->low_stock_threshold;
    }
}
