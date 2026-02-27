<?php

namespace App\I18n;

use Carbon\CarbonImmutable;

class I18nBundleService
{
    private const MESSAGES_FILE = 'messages.php';

    /**
     * @return string[]
     */
    public function locales(): array
    {
        $langPath = app()->langPath();

        if (! is_dir($langPath)) {
            return [];
        }

        $locales = [];

        foreach (scandir($langPath) as $entry) {
            if (! is_string($entry) || $entry === '.' || $entry === '..') {
                continue;
            }

            $messagesPath = $this->messagesPath($entry);

            if (is_file($messagesPath)) {
                $locales[] = $entry;
            }
        }

        sort($locales);

        return $locales;
    }

    public function exists(string $locale): bool
    {
        return is_file($this->messagesPath($locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function messagesFor(string $locale): array
    {
        $path = $this->messagesPath($locale);

        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, mixed> $messages */
        $messages = require $path;

        return $messages;
    }

    public function versionFor(string $locale): ?string
    {
        $path = $this->messagesPath($locale);

        if (! is_file($path)) {
            return null;
        }

        return hash_file('sha256', $path);
    }

    public function lastModifiedFor(string $locale): ?CarbonImmutable
    {
        $path = $this->messagesPath($locale);

        if (! is_file($path)) {
            return null;
        }

        $timestamp = filemtime($path);

        if (! $timestamp) {
            return null;
        }

        return CarbonImmutable::createFromTimestampUTC($timestamp);
    }

    private function messagesPath(string $locale): string
    {
        return app()->langPath($locale.'/'.self::MESSAGES_FILE);
    }
}
