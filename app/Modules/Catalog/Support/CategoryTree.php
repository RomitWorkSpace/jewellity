<?php

namespace App\Modules\Catalog\Support;

use App\Modules\Catalog\Models\Category;
use Illuminate\Support\Collection;

/**
 * In-memory view of the active category tree (one query). Categories are few, so this is
 * cheaper and simpler than recursive queries. A category whose ancestor is inactive is unreachable.
 */
class CategoryTree
{
    /** @var Collection<int, Category> keyed by id */
    private Collection $byId;

    /** @var Collection<int|string, Collection<int, Category>> */
    private Collection $byParent;

    public function __construct()
    {
        $active = Category::active()
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'description', 'image_path', 'meta_title', 'meta_description', 'sort_order']);

        $this->byId = $active->keyBy('id');
        $this->byParent = $active->groupBy(fn (Category $c) => $c->parent_id ?? 0);
    }

    /** The active category with this slug, only if its whole ancestor chain is active too. */
    public function findBySlug(string $slug): ?Category
    {
        $category = $this->byId->firstWhere('slug', $slug);

        if (! $category) {
            return null;
        }

        $parentId = $category->parent_id;
        while ($parentId) {
            $parent = $this->byId->get($parentId);
            if (! $parent) {
                return null; // an ancestor is inactive or deleted
            }
            $parentId = $parent->parent_id;
        }

        return $category;
    }

    /** @return Collection<int, Category> */
    public function roots(): Collection
    {
        return $this->byParent->get(0, collect());
    }

    /** @return Collection<int, Category> */
    public function children(Category $category): Collection
    {
        return $this->byParent->get($category->id, collect());
    }

    /** Root-first list of ancestors (excluding the category itself). @return list<Category> */
    public function ancestors(Category $category): array
    {
        $chain = [];
        $parent = $category->parent_id ? $this->byId->get($category->parent_id) : null;

        while ($parent) {
            array_unshift($chain, $parent);
            $parent = $parent->parent_id ? $this->byId->get($parent->parent_id) : null;
        }

        return $chain;
    }

    /** The category's id plus all active descendants' ids. @return list<int> */
    public function idsWithDescendants(Category $category): array
    {
        $ids = [$category->id];
        $queue = [$category->id];

        while ($queue) {
            foreach ($this->byParent->get(array_shift($queue), []) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }
}
