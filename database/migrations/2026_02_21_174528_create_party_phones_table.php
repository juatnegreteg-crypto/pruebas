<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('phone_number', 20);
            $table->string('type', 20)->default('primary');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['party_id', 'phone_number']);
            $table->index(['party_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_phones');
    }
};
