<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('json_decoder_loop_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loop_id')
                  ->constrained('json_decoder_loops')
                  ->cascadeOnDelete();
            $table->string('display_name', 191);
            $table->string('internal_key', 191);
            $table->json('json_path');
            $table->timestamps();

            $table->index('loop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('json_decoder_loop_fields');
    }
};
