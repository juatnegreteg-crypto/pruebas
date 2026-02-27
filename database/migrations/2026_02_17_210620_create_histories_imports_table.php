<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('histories_imports', function (Blueprint $table) {
            $table->id();
            $table->string('status')->index();
            $table->string('disk')->nullable();
            $table->string('file_path')->nullable();
            $table->json('result')->nullable();
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('histories_imports');
    }
};
