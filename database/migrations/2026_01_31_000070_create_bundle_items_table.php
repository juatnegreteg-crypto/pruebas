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
        Schema::create('bundle_items', function (Blueprint $table) {
            $table->unsignedBigInteger('bundle_id');
            $table->unsignedBigInteger('bundleable_id');
            $table->string('bundleable_type');
            $table->unsignedInteger('quantity')->default(1)->comment('Units of this item within the bundle');
            $table->timestamps();

            $table->primary(['bundle_id', 'bundleable_id', 'bundleable_type']);
            $table->index(['bundleable_id', 'bundleable_type']);

            $table->foreign('bundle_id')
                ->references('catalog_item_id')
                ->on('bundle_details')
                ->cascadeOnDelete();
        });

        if (class_exists(SeedQueue::class)) {
            SeedQueue::on('demo')->add(
                \Database\Seeders\ProductSeeder::class,
                \Database\Seeders\ServiceSeeder::class,
                \Database\Seeders\BundleSeeder::class,
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_items');
    }
};
