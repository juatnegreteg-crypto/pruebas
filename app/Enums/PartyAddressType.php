<?php

namespace App\Enums;

enum PartyAddressType: string
{
    case Primary = 'primary';
    case Billing = 'billing';
    case Shipping = 'shipping';

    /**
     * @return string[]
     */
    public static function optionsForSelect(): array
    {
        return array_map(
            fn (self $case) => $case->value,
            self::cases()
        );
    }
}
