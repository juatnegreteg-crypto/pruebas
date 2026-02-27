<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->timestamps();

            $table->unique('party_id');
            $table->unique('tax_id');
            $table->index('legal_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_organizations');
    }
};
