<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('json_decoder_loop_fields');
        Schema::dropIfExists('json_decoder_loops');
        Schema::dropIfExists('json_decoder_fields');
    }

    public function down(): void
    {
        // Recreate legacy tables (minimal — for rollback only)
        Schema::create('json_decoder_fields', function ($table) {
            $table->id();
            $table->foreignId('decoder_model_id')->constrained('json_decoder_models')->cascadeOnDelete();
            $table->string('api_name', 191);
            $table->string('display_name', 191);
            $table->string('internal_key', 36)->unique();
            $table->json('json_path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('json_decoder_loops', function ($table) {
            $table->id();
            $table->foreignId('decoder_model_id')->constrained('json_decoder_models')->cascadeOnDelete();
            $table->string('api_name', 191);
            $table->string('loop_name', 191);
            $table->string('internal_key', 36)->unique();
            $table->json('json_path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('json_decoder_loop_fields', function ($table) {
            $table->id();
            $table->foreignId('loop_id')->constrained('json_decoder_loops')->cascadeOnDelete();
            $table->string('display_name', 191);
            $table->string('internal_key', 36)->unique();
            $table->json('json_path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
