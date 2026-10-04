<?php

namespace App\Modules\Catalog\Support;

/** What a storefront listing is about: a set of categories, a search, both, or the whole shop. */
final class ListingScope
{
    /** @param list<int>|null $categoryIds null = no category restriction */
    public function __construct(public readonly ?array $categoryIds = null, public readonly ?SearchTerms $search = null) {}
}
