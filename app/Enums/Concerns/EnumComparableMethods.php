<?php

namespace App\Enums\Concerns;

trait EnumComparableMethods
{
    public function is(mixed $other): bool
    {
        return $this === $other;
    }

    public function isAny(mixed ...$others): bool
    {
        foreach ($others as $code) {
            if ($this->is($code)) {
                return true;
            }
        }

        return false;
    }

    public function isNone(mixed ...$others): bool
    {
        return ! $this->isAny(...$others);
    }

    public function isNot(mixed $other): bool
    {
        return ! $this->is($other);
    }

    public function matches(mixed $other): bool
    {
        $isValue = is_int($other) || is_string($other);

        if ($isValue && method_exists(static::class, 'tryFrom')) {
            $other = static::tryFrom($other);
        }

        return $this === $other;
    }

    public function matchesAny(mixed ...$others): bool
    {
        foreach ($others as $code) {
            if ($this->matches($code)) {
                return true;
            }
        }

        return false;
    }

    public function matchesNone(mixed ...$others): bool
    {
        return ! $this->matchesAny(...$others);
    }
}
