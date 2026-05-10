<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_model_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_model_id')->constrained()->cascadeOnDelete();
            $table->string('api_name', 191);
            $table->string('path', 191)->default('entry.cgi');
            $table->string('method', 50)->default('query');
            $table->json('parameters')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('min_version')->default(1);
            $table->unsignedTinyInteger('max_version')->default(99);
            $table->timestamps();

            $table->index(['api_model_id', 'api_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_model_entries');
    }
};
