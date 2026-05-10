<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('json_decoder_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decoder_model_id')
                  ->constrained('json_decoder_models')
                  ->cascadeOnDelete();
            $table->string('api_name', 191);
            $table->string('display_name', 191);
            $table->string('internal_key', 191);
            $table->json('json_path');
            $table->timestamps();

            $table->index('decoder_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('json_decoder_fields');
    }
};
