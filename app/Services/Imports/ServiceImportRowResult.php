<?php

namespace App\Services\Imports;

use App\Models\Service;

/**
 * Result of processing a single row in service import.
 *
 * @codeCoverageIgnore
 */
class ServiceImportRowResult
{
    private function __construct(
        public readonly string $status,
        public readonly array $errors = [],
        public readonly ?Service $service = null
    ) {}

    public static function created(?Service $service = null): self
    {
        return new self('created', service: $service);
    }

    public static function updated(?Service $service = null): self
    {
        return new self('updated', service: $service);
    }

    public static function skipped(): self
    {
        return new self('skipped');
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

    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
