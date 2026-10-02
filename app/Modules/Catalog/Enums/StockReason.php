<?php

namespace App\Modules\Catalog\Enums;

enum StockReason: string
{
    case Initial = 'initial';
    case Restock = 'restock';
    case Correction = 'correction';
    case Damaged = 'damaged';
    case Returned = 'returned';
    case Sale = 'sale';          // written by the Orders module
    case Cancelled = 'cancelled'; // written by the Orders module

    /** Reasons an admin may choose manually. */
    public static function manual(): array
    {
        return [self::Restock, self::Correction, self::Damaged, self::Returned];
    }
}
