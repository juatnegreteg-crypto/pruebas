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
        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('type', 20)->default('primary');
            $table->boolean('is_primary')->default(false);
            $table->string('street');
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code', 20)->nullable();
            $table->string('country');
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['party_id', 'type']);
            $table->index(['party_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_addresses');
    }
};
