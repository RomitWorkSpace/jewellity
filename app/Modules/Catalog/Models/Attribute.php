<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasUniqueSlug;

    protected $fillable = ['name', 'slug'];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('value');
    }
}
