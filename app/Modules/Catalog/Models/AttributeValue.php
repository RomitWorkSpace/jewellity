<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'value', 'slug', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->slug = $m->slug ?: Str::slug($m->value));
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
