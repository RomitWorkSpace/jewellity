<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Concerns\HasUniqueSlug;
use Database\Factories\Catalog\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(CategoryFactory::class)]
class Category extends Model
{
    use HasFactory, HasUniqueSlug, SoftDeletes;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image_path', 'sort_order',
        'is_active', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** IDs of every category below this one (any depth). */
    public function descendantIds(): array
    {
        $byParent = self::query()->whereNotNull('parent_id')->get(['id', 'parent_id'])->groupBy('parent_id');

        $ids = [];
        $queue = [$this->id];
        while ($queue) {
            $current = array_shift($queue);
            foreach ($byParent->get($current, []) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }
}
