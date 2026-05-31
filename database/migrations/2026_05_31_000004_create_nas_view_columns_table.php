<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_view_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('view_id')->constrained('nas_view_tables')->cascadeOnDelete();
            $table->enum('source', ['device', 'custom_field']);
            $table->string('field_key', 64);
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_view_columns');
    }
};
