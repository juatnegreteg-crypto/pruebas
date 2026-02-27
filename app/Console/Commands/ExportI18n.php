<?php

namespace App\Console\Commands;

use App\I18n\I18nBundleService;
use Illuminate\Console\Command;

class ExportI18n extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-i18n {--locale=* : Locale(s) to export}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export i18n messages from lang/ to resources/js/i18n/messages';

    /**
     * Execute the console command.
     */
    public function handle(I18nBundleService $service): int
    {
        $locales = $this->option('locale');

        if (! is_array($locales) || $locales === []) {
            $locales = $service->locales();
        }

        if ($locales === []) {
            $this->warn('No locales found to export.');

            return self::SUCCESS;
        }

        $outputPath = resource_path('js/i18n/messages');

        if (! is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        foreach ($locales as $locale) {
            if (! is_string($locale) || $locale === '') {
                continue;
            }

            if (! $service->exists($locale)) {
                $this->warn("Locale [{$locale}] has no messages.php file.");

                continue;
            }

            $messages = $this->normalizeMessages($service->messagesFor($locale));
            $payload = json_encode(
                $messages,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );

            if ($payload === false) {
                $this->error("Failed to encode messages for locale [{$locale}].");

                continue;
            }

            $content = "/* Generated file. Do not edit manually. */\nexport default {$payload} as const;\n";
            file_put_contents($outputPath.'/'.$locale.'.ts', $content);

            $this->info("Exported locale: {$locale}");
        }

        return self::SUCCESS;
    }

    private function normalizeMessages(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace('@', "{'@'}", $value);
        }

        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeMessages($item);
        }

        return $normalized;
    }
}
