<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportServicesRequest;
use App\Jobs\ProcessServiceImport;
use App\Models\ProcessJob;
use App\Services\Imports\ServiceImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceImportController extends Controller
{
    /**
     * Create a new service-import process.
     *
     * POST /service-imports
     */
    public function store(
        ImportServicesRequest $request,
        ServiceImportService $importService,
    ): JsonResponse {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        // Validate file structure synchronously (fast) — returns 422 immediately if invalid.
        try {
            $importService->validateFile($file);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'type' => url('/problems/invalid-import-file'),
                'title' => 'Invalid import file',
                'status' => 422,
                'detail' => $exception->getMessage(),
                'instance' => $request->getPathInfo(),
            ], 422)
                ->header('Content-Type', 'application/problem+json');
        }

        // Store file to disk so the queue worker can access it later.
        $storedPath = $file->store('imports', 'local');

        $processJob = ProcessJob::query()->create([
            'type' => 'service-import',
            'status' => ProcessJob::STATUS_PENDING,
            'file_path' => $storedPath,
        ]);

        // afterCommit() ensures the ProcessJob row is committed before the worker picks up the job.
        ProcessServiceImport::dispatch($processJob->id)->afterCommit();

        return response()->json([
            'message' => 'Importación encolada correctamente.',
            'import_job_uuid' => $processJob->uuid,
        ], 202);
    }

    /**
     * Show the current state of a service-import process.
     *
     * GET /service-imports/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $processJob = ProcessJob::query()
            ->where('type', 'service-import')
            ->where('uuid', $uuid)
            ->firstOrFail();

        $data = [
            'status' => $processJob->status,
            'total_chunks' => $processJob->total_chunks,
            'processed_chunks' => $processJob->processed_chunks,
        ];

        if ($processJob->status === ProcessJob::STATUS_COMPLETED) {
            $data['result'] = $processJob->result;
        }

        if ($processJob->status === ProcessJob::STATUS_FAILED) {
            $data['error'] = $processJob->error;
        }

        return response()->json($data);
    }

    /**
     * Download the import template spreadsheet.
     *
     * GET /service-imports/template
     */
    public function template(ServiceImportService $importService): StreamedResponse
    {
        $spreadsheet = $importService->generateTemplateSpreadsheet();
        $filename = $importService->templateFilename();

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
