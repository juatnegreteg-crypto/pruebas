<?php

namespace App\Services\Exports;

use App\Exports\ProductsXlsExport;
use App\Jobs\GenerateProductsExportJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductExportService
{
    private const CACHE_PREFIX = 'products_export:';

    private const CACHE_TTL_SECONDS = 86400;

    private const STORAGE_DISK = 'local';

    private const STORAGE_DIR = 'exports/products';

    public function export(array $filters = [], ?string $locale = null)
    {
        try {
            $fileName = 'productos_'.now()->format('Ymd_His').'.xlsx';

            return Excel::download(
                new ProductsXlsExport($filters, $this->resolveLocale($locale)),
                $fileName,
                null,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            );
        } catch (Throwable $e) {
            Log::error('Error exporting products', [
                'message' => $e->getMessage(),
                'filters' => $filters,
            ]);

            abort(500, 'No se pudo generar el archivo de exportacion.');
        }
    }

    public function queueExport(array $filters = [], ?string $locale = null): array
    {
        $exportId = (string) Str::uuid();
        $fileName = 'productos_'.now()->format('Ymd_His').'.xlsx';
        $path = self::STORAGE_DIR.'/'.$exportId.'.xlsx';

        Cache::put(
            $this->cacheKey($exportId),
            [
                'status' => 'pending',
                'file_name' => $fileName,
                'path' => $path,
                'error' => null,
            ],
            now()->addSeconds(self::CACHE_TTL_SECONDS)
        );

        GenerateProductsExportJob::dispatch($exportId, $filters, $path, $this->resolveLocale($locale));

        return [
            'export_id' => $exportId,
            'status' => 'pending',
        ];
    }

    public function getStatus(string $exportId): ?array
    {
        $status = Cache::get($this->cacheKey($exportId));

        if (! is_array($status)) {
            return null;
        }

        return $status;
    }

    public function markCompleted(string $exportId): void
    {
        $status = $this->getStatus($exportId);

        if (! is_array($status)) {
            return;
        }

        $status['status'] = 'completed';
        $status['error'] = null;

        Cache::put(
            $this->cacheKey($exportId),
            $status,
            now()->addSeconds(self::CACHE_TTL_SECONDS)
        );
    }

    public function markProcessing(string $exportId): void
    {
        $status = $this->getStatus($exportId);

        if (! is_array($status)) {
            return;
        }

        $status['status'] = 'processing';

        Cache::put(
            $this->cacheKey($exportId),
            $status,
            now()->addSeconds(self::CACHE_TTL_SECONDS)
        );
    }

    public function markFailed(string $exportId, string $error): void
    {
        $status = $this->getStatus($exportId);

        if (! is_array($status)) {
            return;
        }

        $status['status'] = 'failed';
        $status['error'] = $error;

        Cache::put(
            $this->cacheKey($exportId),
            $status,
            now()->addSeconds(self::CACHE_TTL_SECONDS)
        );
    }

    public function storeExportFile(string $path, array $filters = [], ?string $locale = null): void
    {
        Excel::store(
            new ProductsXlsExport($filters, $this->resolveLocale($locale)),
            $path,
            self::STORAGE_DISK
        );
    }

    public function downloadExport(string $exportId): ?StreamedResponse
    {
        $status = $this->getStatus($exportId);

        if (! is_array($status) || $status['status'] !== 'completed') {
            return null;
        }

        if (! Storage::disk(self::STORAGE_DISK)->exists($status['path'])) {
            return null;
        }

        return Storage::disk(self::STORAGE_DISK)->download(
            $status['path'],
            $status['file_name'],
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    private function cacheKey(string $exportId): string
    {
        return self::CACHE_PREFIX.$exportId;
    }

    private function resolveLocale(?string $locale): string
    {
        if (is_string($locale) && $locale !== '') {
            return $locale;
        }

        return app()->getLocale();
    }
}
