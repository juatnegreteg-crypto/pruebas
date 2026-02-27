<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Throwable;

/**
 * Handles import-related exceptions for API requests and returns RFC 9457 Problem Details responses.
 *
 * Responsibilities:
 * - Handle Excel file type detection failures (NoTypeDetectedException)
 * - Handle Excel validation errors (ExcelValidationException)
 * - Handle invalid argument errors (typically from header validation)
 * - Return RFC 9457-compliant Problem Details JSON responses
 *
 * Does NOT handle:
 * - Laravel validation errors (handled by Laravel's default handler)
 * - Non-API requests (returns null to let other handlers process them)
 * - Generic exceptions (returns null to maintain fail-safe behavior)
 *
 * @see https://www.rfc-editor.org/rfc/rfc9457.html RFC 9457: Problem Details for HTTP APIs
 */
class ImportExceptionHandler
{
    /**
     * Handle import-related exceptions and return a Problem Details response.
     *
     * @param  Throwable  $exception  The exception to handle
     * @param  Request  $request  The HTTP request
     * @return JsonResponse|null Returns null if the exception should not be handled by this handler
     */
    public static function handle(Throwable $exception, Request $request): ?JsonResponse
    {
        // Only handle import-related requests (not all API requests)
        // This prevents interference with normal API operations like model binding
        if (! self::isImportRequest($request)) {
            return null;
        }

        return match (true) {
            $exception instanceof NoTypeDetectedException => self::handleNoTypeDetected($request),
            $exception instanceof ExcelValidationException => self::handleExcelValidation($request),
            $exception instanceof InvalidArgumentException => self::handleInvalidArgument($exception, $request),
            $exception instanceof \RuntimeException => self::handleRuntimeException($exception, $request),
            default => null, // Let other handlers process this exception
        };
    }

    /**
     * Determine if the request is an import-related request.
     *
     * Only handle exceptions for import endpoints to avoid interfering
     * with normal API operations like model binding 404s.
     */
    private static function isImportRequest(Request $request): bool
    {
        return $request->is('api/*/customers/import*') || $request->is('customers/import*');
    }

    /**
     * Handle NoTypeDetectedException - when Excel file type cannot be detected.
     *
     * This exception is thrown by PhpSpreadsheet when it cannot determine
     * the file format (e.g., corrupted file, invalid extension).
     *
     * Returns 400 Bad Request because the file itself is malformed/corrupted,
     * not a content validation issue.
     */
    private static function handleNoTypeDetected(Request $request): JsonResponse
    {
        return self::problemResponse(
            status: 400,
            title: 'Bad Request',
            detail: 'No se pudo detectar el tipo de archivo. El archivo puede estar corrupto o no ser un formato válido (.xlsx, .xls, .csv).',
            type: 'bad-request',
            instance: $request->getPathInfo()
        );
    }

    /**
     * Handle ExcelValidationException - when Excel file content has validation errors.
     *
     * This exception is thrown by Maatwebsite Excel during import processing
     * when data validation fails.
     */
    private static function handleExcelValidation(Request $request): JsonResponse
    {
        return self::problemResponse(
            status: 422,
            title: 'Import failed',
            detail: 'Hay errores de validación en el contenido del archivo.',
            type: 'import-failed',
            instance: $request->getPathInfo()
        );
    }

    /**
     * Handle InvalidArgumentException - typically from header/structure validation failures.
     *
     * This exception is thrown by ExcelFileValidator when required headers
     * are missing or column count is insufficient.
     *
     * Returns 422 Unprocessable Entity because Laravel treats InvalidArgumentException
     * as a validation error. While these are structural issues, the distinction is semantic
     * and Laravel's default behavior takes precedence for consistency.
     */
    private static function handleInvalidArgument(InvalidArgumentException $exception, Request $request): JsonResponse
    {
        return self::problemResponse(
            status: 422,
            title: 'Import failed',
            detail: $exception->getMessage(),
            type: 'import-failed',
            instance: $request->getPathInfo()
        );
    }

    /**
     * Handle RuntimeException - generic unexpected errors during import.
     *
     * This is a catch-all for unexpected RuntimeExceptions during import processing.
     * Returns 500 (Internal Server Error) and does NOT expose the exception message
     * to the client for security reasons.
     *
     * The actual exception is logged internally for debugging purposes.
     */
    private static function handleRuntimeException(\RuntimeException $exception, Request $request): JsonResponse
    {
        // Log the actual error for internal debugging (not exposed to client)
        \Log::error('Import RuntimeException', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return self::problemResponse(
            status: 500,
            title: 'Internal Server Error',
            detail: 'Ocurrió un error inesperado al procesar la importación. Por favor, contacta al administrador del sistema.',
            type: 'internal-server-error',
            instance: $request->getPathInfo()
        );
    }

    /**
     * Create a RFC 9457 Problem Details JSON response.
     *
     * @param  int  $status  HTTP status code
     * @param  string  $title  Short, human-readable summary of the problem type
     * @param  string  $detail  Human-readable explanation specific to this occurrence
     * @param  string  $type  URI reference identifying the problem type
     * @param  string  $instance  URI reference identifying the specific occurrence
     * @param  array  $extra  Additional problem-specific extension fields
     * @return JsonResponse Response with application/problem+json content type
     */
    private static function problemResponse(
        int $status,
        string $title,
        string $detail,
        string $type,
        string $instance,
        array $extra = []
    ): JsonResponse {
        return response()
            ->json(array_merge([
                'type' => url("/problems/{$type}"),
                'title' => $title,
                'status' => $status,
                'detail' => $detail,
                'instance' => $instance,
            ], $extra), $status)
            ->header('Content-Type', 'application/problem+json');
    }
}
