<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NekoOs\LaravelSeedDrain\Support\SeedQueue;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_details', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_item_id')->primary();
            $table->unsignedInteger('duration')->default(0)->comment('Service duration in minutes');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('catalog_item_id')
                ->references('id')
                ->on('catalog_items')
                ->cascadeOnDelete();

            $table->index('duration');
        });

        if (class_exists(SeedQueue::class)) {
            SeedQueue::on('demo')->add(\Database\Seeders\ServiceSeeder::class);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_details');
    }
};
