<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\I18n\I18nBundleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class I18nController extends Controller
{
    public function index(I18nBundleService $service): JsonResponse
    {
        return response()->json([
            'locales' => $service->locales(),
        ]);
    }

    public function show(Request $request, string $locale, I18nBundleService $service): JsonResponse
    {
        if (! $service->exists($locale)) {
            abort(404);
        }

        $payload = [
            'locale' => $locale,
            'version' => $service->versionFor($locale),
            'messages' => $service->messagesFor($locale),
        ];

        $response = response()->json($payload);
        $etag = $payload['version'];
        $lastModified = $service->lastModifiedFor($locale);

        if (is_string($etag) && $etag !== '') {
            $response->setEtag($etag);
        }

        if ($lastModified) {
            $response->setLastModified($lastModified);
        }

        $response->isNotModified($request);

        return $response;
    }
}
