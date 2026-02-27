<?php

namespace App\Jobs;

use App\Imports\CustomersUpsertImport;
use App\Models\CustomerImport;
use App\Services\Imports\CustomerImportProcessor;
use App\Services\Imports\CustomerImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessCustomerImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public readonly int $customerImportId,
        public readonly string $filePath,
        public readonly string $disk,
    ) {}

    public function handle(
        CustomerImportProcessor $processor,
        CustomerImportService $importService,
    ): void {
        $this->disableExecutionTimeLimit();

        $customerImport = CustomerImport::query()->find($this->customerImportId);

        if (! $customerImport) {
            throw new \RuntimeException('No se encontró el registro de seguimiento de importación.');
        }

        $customerImport->update([
            'status' => CustomerImport::STATUS_PROCESSING,
            'started_at' => now(),
            'message' => 'Importación en proceso.',
            'error' => null,
        ]);

        if (! Storage::disk($this->disk)->exists($this->filePath)) {
            throw new \RuntimeException('El archivo de importación no existe en el almacenamiento.');
        }

        $absoluteFilePath = Storage::disk($this->disk)->path($this->filePath);
        $uploadedFile = new UploadedFile(
            $absoluteFilePath,
            basename($this->filePath),
            null,
            null,
            true
        );

        $importService->validateFile($uploadedFile);

        $customersImport = new CustomersUpsertImport($processor, $importService);

        $customersImport->import($absoluteFilePath);
        $result = $customersImport->result()->toArray();

        $customerImport->update([
            'status' => CustomerImport::STATUS_COMPLETED,
            'result' => $result,
            'message' => 'Importación finalizada exitosamente.',
            'finished_at' => now(),
        ]);

        Log::info('Customer import processed in queue.', [
            'customer_import_id' => $this->customerImportId,
            'file_path' => $this->filePath,
            'result' => $result,
        ]);

        $this->deleteTemporaryFile();
    }

    public function failed(Throwable $exception): void
    {
        $customerImport = CustomerImport::query()->find($this->customerImportId);

        if ($customerImport) {
            $customerImport->update([
                'status' => CustomerImport::STATUS_FAILED,
                'message' => 'La importación finalizó con errores.',
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
        }

        Log::error('Customer import failed in queue.', [
            'customer_import_id' => $this->customerImportId,
            'file_path' => $this->filePath,
            'message' => $exception->getMessage(),
        ]);

        $this->deleteTemporaryFile();
    }

    private function deleteTemporaryFile(): void
    {
        if (Storage::disk($this->disk)->exists($this->filePath)) {
            Storage::disk($this->disk)->delete($this->filePath);
        }
    }

    private function disableExecutionTimeLimit(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');
    }
}
