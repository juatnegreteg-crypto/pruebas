<?php

namespace App\Enums;

enum UnitOfMeasure: string
{
    case Unit = 'unit';
    case Gram = 'gram';
    case Kilogram = 'kilogram';
    case Meter = 'meter';
    case Centimeter = 'centimeter';
    case Millimeter = 'millimeter';
    case Liter = 'liter';
    case Milliliter = 'milliliter';

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
