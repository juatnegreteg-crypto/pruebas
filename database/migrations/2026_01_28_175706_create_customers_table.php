<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NekoOs\LaravelSeedDrain\Support\SeedQueue;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('full_name', 255);
            $table->string('email', 255)->unique();

            $table->string('document_type', 20);
            $table->string('document_number', 50)->unique();

            $table->string('phone_number', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Helpful indexes for listing/searching
            $table->index('full_name');
            $table->index('created_at');
        });

        if (class_exists(SeedQueue::class)) {
            SeedQueue::on('demo')->add(\Database\Seeders\CustomerSeeder::class);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
