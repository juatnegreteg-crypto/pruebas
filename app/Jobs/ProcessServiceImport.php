<?php

namespace App\Jobs;

use App\Imports\ServicesUpsertImport;
use App\Models\ProcessJob;
use App\Services\Imports\ServiceImportProcessor;
use App\Services\Imports\ServiceImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Processes a service import spreadsheet in a background queue.
 *
 * Flow:
 * 1. Controller validates the file structure, stores it, and dispatches this job.
 * 2. Job imports via ServicesUpsertImport (no re-validation needed).
 * 3. Result (or error) is persisted on the ImportJob record.
 * 4. Frontend polls the status endpoint until completion.
 *
 * Key design decisions:
 * - File validation is done in the controller BEFORE dispatch (fast, returns 422).
 * - Row counting uses listWorksheetInfo() which reads only XML metadata,
 *   NOT load() which reads all cell data into memory.
 * - Maatwebsite's import() is called with a raw file path string (no UploadedFile wrapper).
 */
class ProcessServiceImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Max attempts before marking the job as failed.
     */
    public int $tries = 1;

    /**
     * Max execution time (seconds) for this job.
     */
    public int $timeout = 600;

    public function __construct(
        public readonly int $processJobId,
    ) {}

    public function handle(
        ServiceImportProcessor $processor,
        ServiceImportService $importService,
    ): void {
        $processJob = ProcessJob::findOrFail($this->processJobId);

        Log::info('[ProcessServiceImport] Starting import', ['process_job_id' => $processJob->id]);

        try {
            $filePath = Storage::disk('local')->path($processJob->file_path);

            if (! file_exists($filePath)) {
                $processJob->markFailed('El archivo de importación no fue encontrado.');

                return;
            }

            // Count data rows using lightweight metadata read (no cell data loaded).
            $totalChunks = $this->estimateTotalChunks($filePath);
            $processJob->markProcessing($totalChunks);

            Log::info('[ProcessServiceImport] Estimated chunks', [
                'process_job_id' => $processJob->id,
                'total_chunks' => $totalChunks,
            ]);

            $servicesImport = new ServicesUpsertImport(
                $processor,
                $importService,
                500,
                $processJob,
            );

            // Maatwebsite's Importable::import() accepts a raw file path string.
            $servicesImport->import($filePath);

            $processJob->markCompleted($servicesImport->result()->toArray());

            Log::info('[ProcessServiceImport] Import completed', [
                'process_job_id' => $processJob->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            $processJob->markFailed($e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $processJob->markFailed('La importación falló por un error interno. Intenta de nuevo más tarde.');
        } finally {
            Storage::disk('local')->delete($processJob->file_path);
        }
    }

    /**
     * Estimate how many chunks Maatwebsite will produce.
     *
     * Uses listWorksheetInfo() which reads only the worksheet dimension
     * from the XML metadata — it does NOT load cell data into memory.
     * This is safe for large files (100k+ rows).
     */
    private function estimateTotalChunks(string $filePath, int $chunkSize = 500): int
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $info = $reader->listWorksheetInfo($filePath);
            $totalRows = $info[0]['totalRows'] ?? 0;
            $dataRows = max(0, $totalRows - 1); // minus header row

            return $dataRows > 0 ? (int) ceil($dataRows / $chunkSize) : 1;
        } catch (\Throwable) {
            return 0;
        }
    }
}
