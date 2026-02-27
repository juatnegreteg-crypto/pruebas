<?php

namespace App\Jobs;

use App\Services\Exports\ProductExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateProductsExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 1200;

    public function __construct(
        public string $exportId,
        public array $filters,
        public string $path,
        public string $locale,
    ) {}

    public function handle(ProductExportService $service): void
    {
        try {
            $service->markProcessing($this->exportId);
            $service->storeExportFile($this->path, $this->filters, $this->locale);
            $service->markCompleted($this->exportId);
        } catch (Throwable $exception) {
            Log::error('Error generating products export in queue', [
                'export_id' => $this->exportId,
                'message' => $exception->getMessage(),
            ]);

            $service->markFailed(
                $this->exportId,
                'No se pudo generar el archivo de exportacion.'
            );

            throw $exception;
        }
    }
}
