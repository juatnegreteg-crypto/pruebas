<?php

namespace App\Http\Controllers\Api\V1\Iam;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $skills = Skill::query()
            ->bySearch($request->query('search'))
            ->orderBy('name')
            ->get()
            ->map(fn (Skill $skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'description' => $skill->description,
                'is_active' => $skill->is_active,
            ]);

        return response()->json([
            'data' => $skills,
        ]);
    }
}
