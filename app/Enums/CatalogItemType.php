<?php

namespace App\Enums;

use App\Enums\Concerns\EnumComparableMethods;
use App\Enums\Contracts\EnumComparable;

enum CatalogItemType: string implements EnumComparable
{
    use EnumComparableMethods;

    case PRODUCT = 'product';
    case SERVICE = 'service';
    case BUNDLE = 'bundle';
}
