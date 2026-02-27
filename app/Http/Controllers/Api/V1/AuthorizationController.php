<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Iam\AuthorizationPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    public function show(Request $request, AuthorizationPayloadBuilder $payloadBuilder): JsonResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        return response()->json([
            'data' => $payloadBuilder->build($user),
        ]);
    }
}
