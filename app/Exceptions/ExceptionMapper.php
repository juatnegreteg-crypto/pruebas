<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\DomainErrorCodeException;
use App\Exceptions\Contracts\ExceptionHttpMappingRegistrar;
use App\Exceptions\Contracts\TranslatableDomainException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExceptionMapper
{
    /**
     * @var array<int, array{
     *     type: class-string<Throwable>,
     *     to_http: Closure(Throwable): Response,
     *     report: bool
     * }>
     */
    private array $mappings = [];

    /**
     * Fast-path index for exact class matches.
     *
     * @var array<class-string<Throwable>, array{
     *     type: class-string<Throwable>,
     *     to_http: Closure(Throwable): Response,
     *     report: bool
     * }>
     */
    private array $exactMappings = [];

    /**
     * @param  iterable<ExceptionHttpMappingRegistrar>  $registrars
     */
    public function __construct(iterable $registrars = [])
    {
        foreach ($registrars as $registrar) {
            $registrar->register($this);
        }
    }

    public function toHttp(Throwable $exception): ?Response
    {
        $mapping = $this->resolve($exception);

        if ($mapping === null) {
            return null;
        }

        return $mapping['to_http']($exception);
    }

    public function shouldReport(Throwable $exception): bool
    {
        $mapping = $this->resolve($exception);

        if ($mapping === null) {
            return true;
        }

        return $mapping['report'];
    }

    /**
     * @param  class-string<Throwable>  $type
     * @param  Closure(Throwable): Response  $toHttp
     */
    public function register(string $type, Closure $toHttp, bool $report = true): self
    {
        $mapping = [
            'type' => $type,
            'to_http' => $toHttp,
            'report' => $report,
        ];
        $this->mappings[] = $mapping;

        // Preserve first-registration precedence for the same class.
        if (! isset($this->exactMappings[$type])) {
            $this->exactMappings[$type] = $mapping;
        }

        return $this;
    }

    /**
     * @param  class-string<Throwable>  $type
     */
    public function registerValidation(
        string $type,
        string $field,
        int $statusCode = 422,
        bool $report = false,
    ): self {
        return $this->register(
            type: $type,
            toHttp: function (Throwable $exception) use ($field, $statusCode): JsonResponse {
                $message = $this->resolveMessage($exception);

                $validationException = ValidationException::withMessages([
                    $field => [$message],
                ])->status($statusCode);

                return response()->json([
                    'code' => $this->resolveCode($exception),
                    'message' => $validationException->getMessage(),
                    'errors' => $validationException->errors(),
                ], $validationException->status);
            },
            report: $report,
        );
    }

    private function resolveMessage(Throwable $exception): string
    {
        if (! $exception instanceof TranslatableDomainException) {
            return $exception->getMessage();
        }

        $translated = __($exception->translationKey(), $exception->translationContext());

        if ($translated === $exception->translationKey()) {
            return $exception->getMessage();
        }

        return $translated;
    }

    private function resolveCode(Throwable $exception): string
    {
        if (! $exception instanceof DomainErrorCodeException) {
            return 'UNSPECIFIED_ERROR';
        }

        return $exception->errorCode();
    }

    /**
     * @return array{
     *     type: class-string<Throwable>,
     *     to_http: Closure(Throwable): Response,
     *     report: bool
     * }|null
     */
    private function resolve(Throwable $exception): ?array
    {
        $exceptionClass = $exception::class;

        if (isset($this->exactMappings[$exceptionClass])) {
            return $this->exactMappings[$exceptionClass];
        }

        foreach ($this->mappings as $mapping) {
            if ($exception instanceof $mapping['type']) {
                return $mapping;
            }
        }

        return null;
    }
}
