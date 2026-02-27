<?php

namespace App\Jobs;

use App\Models\ProcessJob;
use App\Models\Service;
use App\Services\Exports\ServiceExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * Generates a service export spreadsheet in a background queue.
 *
 * Flow:
 * 1. Controller creates a ProcessJob (pending, type=service_export) and dispatches this job.
 * 2. Job queries services in chunks, writes each chunk to the spreadsheet,
 *    and updates ProcessJob progress after each chunk.
 * 3. Result (or error) is persisted on the ProcessJob record.
 * 4. Frontend polls the status endpoint until completion, then triggers download.
 */
class ProcessServiceExport implements ShouldQueue
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
        public readonly string $locale,
    ) {}

    public function handle(ServiceExportService $exportService): void
    {
        $processJob = ProcessJob::findOrFail($this->processJobId);

        Log::info('[ProcessServiceExport] Starting export', ['process_job_id' => $processJob->id]);

        try {
            app()->setLocale($this->locale);

            $chunkSize = 500;
            $totalRows = Service::query()->count();
            $totalChunks = max(1, (int) ceil($totalRows / $chunkSize));

            $processJob->markProcessing($totalChunks);

            Log::info('[ProcessServiceExport] Estimated chunks', [
                'process_job_id' => $processJob->id,
                'total_rows' => $totalRows,
                'total_chunks' => $totalChunks,
            ]);

            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(trans('exports.services.sheet'));

            // Write styled headers (bold, blue background, auto-sized columns)
            $headers = $exportService->exportHeaders();
            foreach ($headers as $colIndex => $header) {
                $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue("{$col}1", $header);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $lastCol = Coordinate::stringFromColumnIndex(count($headers));
            $headerRange = "A1:{$lastCol}1";
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD6E4F0');

            // Write data in chunks
            $rowIndex = 2;
            Service::query()
                ->with('catalogItem')
                ->orderBy('catalog_item_id')
                ->chunk($chunkSize, function ($services) use ($sheet, &$rowIndex, $processJob, $exportService) {
                    foreach ($services as $service) {
                        $row = $exportService->normalizeRow($service);
                        $sheet->fromArray($row, null, "A{$rowIndex}", true);
                        $rowIndex++;
                    }

                    $processJob->incrementProgress();
                });

            // Save file
            $filePath = 'exports/'.Str::uuid().'.xlsx';
            $fullPath = Storage::disk('local')->path($filePath);

            $dir = dirname($fullPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $writer = new XlsxWriter($spreadsheet);
            $writer->save($fullPath);
            $spreadsheet->disconnectWorksheets();

            $processJob->update(['file_path' => $filePath]);
            $processJob->markCompleted(['total_rows' => $rowIndex - 2]);

            Log::info('[ProcessServiceExport] Export completed', [
                'process_job_id' => $processJob->id,
                'total_rows' => $rowIndex - 2,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $processJob->markFailed('La exportación falló por un error interno. Intenta de nuevo más tarde.');
        }
    }
}
