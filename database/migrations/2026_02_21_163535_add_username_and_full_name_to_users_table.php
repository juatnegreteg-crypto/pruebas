<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('name');
            $table->string('full_name')->nullable()->after('username');
        });

        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            $baseUsername = trim((string) $user->name);
            $baseUsername = $baseUsername !== '' ? $baseUsername : "user-{$user->id}";

            $candidate = $baseUsername;
            $suffix = 1;

            while (
                DB::table('users')
                    ->where('username', $candidate)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                $suffix++;
                $candidate = "{$baseUsername}-{$suffix}";
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'username' => $candidate,
                ]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'full_name']);
        });
    }
};
