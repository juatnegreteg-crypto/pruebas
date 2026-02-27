<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessServiceExport;
use App\Models\ProcessJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manages the service-exports resource.
 *
 * A service-export is an async process with a full lifecycle:
 * pending → processing → completed | failed.
 * It generates an artifact (XLSX file) that can be downloaded.
 *
 * Routes:
 *   POST /api/v1/service-exports          – start an export job (202)
 *   GET  /api/v1/service-exports/{uuid}   – poll status (JSON)
 *                                           or download file (?format=xlsx)
 */
class ServiceExportController extends Controller
{
    /**
     * Start an asynchronous service export job.
     *
     * Returns 202 Accepted with the job UUID so the client can poll.
     */
    public function store(): JsonResponse
    {
        $locale = request()->input('locale', app()->getLocale());

        $processJob = ProcessJob::create([
            'type' => 'service_export',
            'status' => ProcessJob::STATUS_PENDING,
        ]);

        ProcessServiceExport::dispatch($processJob->id, $locale);

        return response()->json(['uuid' => $processJob->uuid], 202);
    }

    /**
     * Show the current state of an export job.
     *
     * When called normally, returns JSON with status and progress.
     * When ?format=xlsx is provided and the job is completed,
     * streams the generated file directly (content negotiation).
     *
     * The client may pass ?filename=<name> to suggest the download filename.
     * That value is generated in the browser with the user's local timezone.
     */
    public function show(string $uuid): JsonResponse|StreamedResponse
    {
        $processJob = ProcessJob::query()
            ->where('uuid', $uuid)
            ->where('type', 'service_export')
            ->firstOrFail();

        // Content negotiation: ?format=xlsx → stream the completed file
        if (request()->query('format') === 'xlsx') {
            abort_if(
                $processJob->status !== ProcessJob::STATUS_COMPLETED,
                404,
                'Export not ready.'
            );

            $disk = Storage::disk('local');
            abort_unless($disk->exists($processJob->file_path), 404, 'Export file not found.');

            $filename = basename((string) request()->query('filename', 'servicios_export.xlsx'));
            if (! str_ends_with(strtolower($filename), '.xlsx')) {
                $filename .= '.xlsx';
            }

            return $disk->download($processJob->file_path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return response()->json([
            'uuid' => $processJob->uuid,
            'status' => $processJob->status,
            'processed_chunks' => $processJob->processed_chunks,
            'total_chunks' => $processJob->total_chunks,
            'result' => $processJob->result,
            'error' => $processJob->error,
        ]);
    }
}
