<?php

namespace App\Enums\Contracts;

interface EnumComparable
{
    public function is(mixed $other): bool;

    public function isAny(mixed ...$others): bool;

    public function isNone(mixed ...$others): bool;

    public function isNot(mixed $other): bool;

    public function matches(mixed $other): bool;

    public function matchesAny(mixed ...$others): bool;

    public function matchesNone(mixed ...$others): bool;
}
