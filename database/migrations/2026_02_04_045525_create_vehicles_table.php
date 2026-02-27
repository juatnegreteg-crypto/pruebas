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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('plate', 20)->unique();
            $table->string('vin', 50)->nullable()->unique();
            $table->string('make', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year');
            $table->string('type', 50)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('fuel_type', 30)->nullable();
            $table->string('transmission', 30)->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['make', 'model']);
        });

        if (class_exists(SeedQueue::class)) {
            SeedQueue::on('demo')->add(\Database\Seeders\VehicleSeeder::class);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
