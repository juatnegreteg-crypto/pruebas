<?php

use App\Exceptions\Contracts\ExceptionHttpMappingRegistrar;
use App\Exceptions\ExceptionMapper;
use App\Exceptions\ImportExceptionHandler;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureApiClient;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSetupIsAvailable;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'api.client' => EnsureApiClient::class,
            'permission' => EnsurePermission::class,
            'setup.available' => EnsureSetupIsAvailable::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(fn (\Throwable $exception, $request) => ImportExceptionHandler::handle($exception, $request));

        $registrars = array_map(
            fn (string $registrarClass): ExceptionHttpMappingRegistrar => app()->make($registrarClass),
            config('exceptions.mapping_registrars', []),
        );

        $mapper = new ExceptionMapper($registrars);

        $exceptions->render(fn (\Throwable $exception) => $mapper->toHttp($exception));

        $exceptions->report(function (\Throwable $exception) use ($mapper) {
            return ! $mapper->shouldReport($exception);
        });
    })->create();
