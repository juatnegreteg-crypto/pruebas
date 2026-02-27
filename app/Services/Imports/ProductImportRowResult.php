<?php

namespace App\Services\Imports;

/**
 * Result of processing a single row in product import.
 *
 * @codeCoverageIgnore
 */
class ProductImportRowResult
{
    private function __construct(
        public readonly string $status,
        public readonly array $errors = []
    ) {}

    public static function created(): self
    {
        return new self('created');
    }

    public static function updated(): self
    {
        return new self('updated');
    }

    public static function failed(array $errors): self
    {
        return new self('failed', $errors);
    }

    public function isCreated(): bool
    {
        return $this->status === 'created';
    }

    public function isUpdated(): bool
    {
        return $this->status === 'updated';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
