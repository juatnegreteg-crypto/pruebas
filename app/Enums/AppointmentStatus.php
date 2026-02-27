<?php

namespace App\Enums;

use App\Enums\Concerns\EnumComparableMethods;
use App\Enums\Contracts\EnumComparable;

enum AppointmentStatus: string implements EnumComparable
{
    use EnumComparableMethods;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

}
