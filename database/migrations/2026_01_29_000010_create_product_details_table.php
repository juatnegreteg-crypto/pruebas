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
        Schema::create('product_details', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_item_id')->primary();
            $table->string('sku')->nullable()->comment('Stock Keeping Unit for inventory tracking');
            $table->unsignedInteger('stock')->default(0)->comment('Units available in inventory');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('catalog_item_id')
                ->references('id')
                ->on('catalog_items')
                ->cascadeOnDelete();

            $table->index('sku');
        });

        if (class_exists(SeedQueue::class)) {
            SeedQueue::on('demo')->add(\Database\Seeders\ProductSeeder::class);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
