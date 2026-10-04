<?php

namespace App\Modules\Catalog\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * A parsed storefront search query. Every word must match somewhere (name, descriptions, SKU,
 * category or an attribute value such as "gold"); results are ranked by where and how well they match.
 *
 * Words match at the START of a word, not anywhere inside it: "ear" finds "earrings", but "ring" does
 * not (it would wrongly match every pair of earrings). Plurals match both ways ("earring" / "earrings").
 * SKUs are codes, so they match anywhere ("2041" finds "JW-2041").
 *
 * This is deliberately plain SQL `LIKE` so it behaves identically on MySQL/MariaDB with no extra
 * infrastructure and supports partial words ("ear" finds "earrings"). It is comfortable into the
 * tens of thousands of products; beyond that, swap this class for FULLTEXT or a search engine
 * (Meilisearch/Typesense) behind the same two methods: apply() and orderBy().
 */
final class SearchTerms
{
    public const MAX_LENGTH = 100;

    public const MAX_TERMS = 6;

    /** @param list<string> $terms lower-cased words */
    private function __construct(public readonly string $raw, public readonly array $terms) {}

    /** Returns null when there is nothing searchable (empty, whitespace, not a string). */
    public static function parse(mixed $input): ?self
    {
        if (! is_string($input)) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/u', ' ', mb_substr($input, 0, self::MAX_LENGTH)) ?? '');
        if ($clean === '') {
            return null;
        }

        $terms = array_values(array_unique(array_slice(explode(' ', mb_strtolower($clean)), 0, self::MAX_TERMS)));

        return new self($clean, $terms);
    }

    /** Escape LIKE wildcards so "100%" or "_" are searched literally. */
    private static function like(string $term, string $before = '%', string $after = '%'): string
    {
        return $before.addcslashes($term, '\\%_').$after;
    }

    /** The stem used for matching: a trailing "s" is dropped so singular and plural find each other. */
    private static function stem(string $term): string
    {
        return mb_strlen($term) > 3 && str_ends_with($term, 's') ? mb_substr($term, 0, -1) : $term;
    }

    /**
     * SQL conditions (and bindings) for "a word in $column starts with the term".
     *
     * @return array{0: string, 1: list<string>}
     */
    private static function wordStart(string $column, string $term): array
    {
        $stem = self::stem($term);

        return [
            "($column like ? or $column like ? or $column like ?)",
            [self::like($stem, '', '%'), self::like($stem, '% ', '%'), self::like($stem, '%-', '%')],
        ];
    }

    /** Restrict a products query to those matching every term. */
    public function apply(Builder $products): Builder
    {
        foreach ($this->terms as $term) {
            $sku = self::like($term);

            $products->where(function (Builder $w) use ($term, $sku) {
                foreach (['products.name', 'products.short_description', 'products.description'] as $column) {
                    [$sql, $bindings] = self::wordStart($column, $term);
                    $w->orWhereRaw($sql, $bindings);
                }

                $w->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where(function ($x) use ($term, $sku) {
                    [$sql, $bindings] = self::wordStart('attribute_values.value', $term);
                    $x->where('sku', 'like', $sku)
                        ->orWhereHas('attributeValues', fn ($a) => $a->whereRaw($sql, $bindings));
                }));

                $w->orWhereHas('categories', function ($c) use ($term) {
                    [$sql, $bindings] = self::wordStart('categories.name', $term);
                    $c->where('is_active', true)->whereRaw($sql, $bindings);
                });
            });
        }

        return $products;
    }

    /** Order by relevance (best first), then newest. */
    public function orderBy(Builder $products): Builder
    {
        [$sql, $bindings] = $this->score();

        return $products->orderByRaw("($sql) desc", $bindings)->latest('products.published_at');
    }

    /** @return array{0: string, 1: list<string>} */
    private function score(): array
    {
        $parts = [];
        $bindings = [];
        $add = function (string $sql, array $values) use (&$parts, &$bindings) {
            $parts[] = $sql;
            array_push($bindings, ...$values);
        };

        if (count($this->terms) > 1) { // the whole phrase appearing in the name is the best signal
            $add('(case when products.name like ? then 15 else 0 end)', [self::like(mb_strtolower($this->raw))]);
        }

        foreach ($this->terms as $term) {
            $stem = self::stem($term);
            $add('(case when products.name like ? then 10 else 0 end)', [self::like($stem, '', '%')]);   // name starts with it
            $add('(case when products.name like ? or products.name like ? then 8 else 0 end)', [self::like($stem, '% ', '%'), self::like($stem, '%-', '%')]); // a later word starts with it
            $add('(case when exists (select 1 from product_variants v where v.product_id = products.id and v.deleted_at is null and v.sku = ?) then 12 else 0 end)', [$term]);
            $add('(case when exists (select 1 from category_product cp join categories c on c.id = cp.category_id where cp.product_id = products.id and c.deleted_at is null and (c.name like ? or c.name like ?)) then 3 else 0 end)', [self::like($stem, '', '%'), self::like($stem, '% ', '%')]);
            $add('(case when exists (select 1 from product_variants v join variant_attribute_value vav on vav.product_variant_id = v.id join attribute_values av on av.id = vav.attribute_value_id where v.product_id = products.id and v.deleted_at is null and (av.value like ? or av.value like ?)) then 2 else 0 end)', [self::like($stem, '', '%'), self::like($stem, '% ', '%')]);
            $add('(case when products.short_description like ? or products.short_description like ? then 2 else 0 end)', [self::like($stem, '', '%'), self::like($stem, '% ', '%')]);
            $add('(case when products.description like ? or products.description like ? then 1 else 0 end)', [self::like($stem, '', '%'), self::like($stem, '% ', '%')]);
        }

        return [implode(' + ', $parts), $bindings];
    }
}
