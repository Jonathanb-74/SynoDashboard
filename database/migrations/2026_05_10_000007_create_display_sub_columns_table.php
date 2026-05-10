<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('display_sub_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('column_id')->constrained('display_columns')->cascadeOnDelete();
            $table->string('label', 191);
            $table->json('json_path');
            $table->string('internal_key', 36)->unique();
            $table->string('transformer', 50)->nullable();
            $table->json('transformer_config')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['column_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('display_sub_columns');
    }
};
