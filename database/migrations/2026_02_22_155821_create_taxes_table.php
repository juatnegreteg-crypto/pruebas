<?php

use Database\Seeders\TaxSeeder;
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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50);
            $table->string('jurisdiction', 100);
            $table->decimal('rate', 8, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('code');
            $table->index('name');
        });

        SeedQueue::on('demo')
            ->add(TaxSeeder::class);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
