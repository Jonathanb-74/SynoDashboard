<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_api_available', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nas_id')
                  ->constrained('nas_devices')
                  ->cascadeOnDelete();
            $table->string('api_name', 191);
            $table->string('path', 191);
            $table->unsignedTinyInteger('min_version')->default(1);
            $table->unsignedTinyInteger('max_version')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['nas_id', 'api_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_api_available');
    }
};
