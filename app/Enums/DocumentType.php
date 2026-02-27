<?php

namespace App\Enums;

enum DocumentType: string
{
    case CC = 'CC';
    case CE = 'CE';
    case NIT = 'NIT';
    case PP = 'PP';
    case TI = 'TI';

    /**
     * Valores y etiquetas para usar en selects (formularios, Inertia).
     *
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
