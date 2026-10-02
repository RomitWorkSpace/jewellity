<?php

namespace App\Modules\Catalog\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** Fills `slug` from `name` on create, guaranteeing uniqueness (trashed rows included). */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::creating(function ($model) {
            if (blank($model->slug)) {
                $model->slug = static::uniqueSlug($model->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;

        while (static::query()->when(
            in_array(SoftDeletes::class, class_uses_recursive(static::class)),
            fn ($q) => $q->withTrashed(),
        )->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
