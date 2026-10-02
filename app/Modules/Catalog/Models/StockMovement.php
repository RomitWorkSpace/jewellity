<?php

namespace App\Modules\Catalog\Models;

use App\Models\User;
use App\Modules\Catalog\Enums\StockReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Append-only: rows are never updated or deleted. */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_variant_id', 'quantity_change', 'quantity_after', 'reason', 'note',
        'reference_type', 'reference_id', 'user_id',
    ];

    protected function casts(): array
    {
        return ['reason' => StockReason::class, 'quantity_change' => 'integer', 'quantity_after' => 'integer'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
