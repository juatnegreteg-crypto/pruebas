<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('party_id')
                ->nullable()
                ->after('id')
                ->constrained('parties')
                ->nullOnDelete();
            $table->unique('party_id');
        });

        Schema::table('technicians', function (Blueprint $table): void {
            $table->foreignId('party_id')
                ->nullable()
                ->after('user_id')
                ->constrained('parties')
                ->nullOnDelete();
            $table->index('party_id');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('party_id')
                ->nullable()
                ->after('id')
                ->constrained('parties')
                ->nullOnDelete();
            $table->index('party_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['party_id']);
            $table->dropConstrainedForeignId('party_id');
        });

        Schema::table('technicians', function (Blueprint $table): void {
            $table->dropIndex(['party_id']);
            $table->dropConstrainedForeignId('party_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['party_id']);
            $table->dropConstrainedForeignId('party_id');
        });
    }
};
