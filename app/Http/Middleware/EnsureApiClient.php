<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return response()->json([
                'error' => 'unauthorized',
                'errorCode' => 'api_client_missing',
            ], 401);
        }

        $client = $this->resolveClient($token);

        if (! $client) {
            return response()->json([
                'error' => 'unauthorized',
                'errorCode' => 'api_client_invalid',
            ], 401);
        }

        $client->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || $header === '') {
            return null;
        }

        if (str_starts_with($header, 'Bearer ')) {
            $token = trim(substr($header, 7));

            return $token !== '' ? $token : null;
        }

        return null;
    }

    private function resolveClient(string $token): ?ApiClient
    {
        $clients = ApiClient::query()->thatAreActive()->get();

        foreach ($clients as $client) {
            if ($client->matchesKey($token)) {
                return $client;
            }
        }

        return null;
    }
}
