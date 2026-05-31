<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nas_id')->constrained('nas_devices')->cascadeOnDelete();
            $table->foreignId('definition_id')->constrained('nas_custom_field_definitions')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['nas_id', 'definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_custom_field_values');
    }
};
