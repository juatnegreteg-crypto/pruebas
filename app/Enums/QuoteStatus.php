<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'destructive',
        };
    }

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
