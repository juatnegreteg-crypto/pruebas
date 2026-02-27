<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bundle_details', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_item_id')->primary();
            $table->string('discount_strategy')->nullable()->comment('Strategy or code used to compute bundle discounts');
            $table->unsignedInteger('items_count')->default(0)->comment('Cached total items included in the bundle');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('catalog_item_id')
                ->references('id')
                ->on('catalog_items')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_details');
    }
};
