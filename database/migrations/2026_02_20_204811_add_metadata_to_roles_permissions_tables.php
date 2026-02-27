<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NekoOs\LaravelSeedDrain\Support\SeedQueue;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->boolean('is_technician_profile')->default(false)->after('is_active');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->string('module')->nullable()->after('description');
            $table->string('action')->nullable()->after('module');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('profile_id')->references('id')->on('roles')->nullOnDelete();
        });

        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->unique(['model_id', 'model_type'], 'model_has_roles_single_role_per_model');
        });

        SeedQueue::add(\Database\Seeders\IamSeeder::class);
    }

    public function down(): void
    {
        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->dropUnique('model_has_roles_single_role_per_model');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropColumn(['description', 'module', 'action']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['profile_id']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['slug', 'description', 'is_active', 'is_technician_profile']);
        });
    }
};
