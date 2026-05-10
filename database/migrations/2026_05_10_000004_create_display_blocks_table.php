<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('display_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decoder_model_id')->constrained('json_decoder_models')->cascadeOnDelete();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['decoder_model_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('display_blocks');
    }
};
