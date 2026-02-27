<?php

namespace App\Http\Middleware;

use App\Models\Profile;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupIsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        $superAdminProfileId = Profile::query()
            ->where(function ($query): void {
                $query->where('slug', 'super-admin')
                    ->orWhere('name', 'Super Admin');
            })
            ->value('id');

        $hasSuperAdmin = false;

        if ($superAdminProfileId !== null) {
            $hasSuperAdmin = User::query()
                ->where('profile_id', $superAdminProfileId)
                ->exists()
                || DB::table(config('permission.table_names.model_has_roles'))
                    ->where(config('permission.column_names.role_pivot_key') ?? 'role_id', $superAdminProfileId)
                    ->where('model_type', (new User)->getMorphClass())
                    ->exists();
        }

        if ($hasSuperAdmin) {
            abort(404);
        }

        return $next($request);
    }
}
