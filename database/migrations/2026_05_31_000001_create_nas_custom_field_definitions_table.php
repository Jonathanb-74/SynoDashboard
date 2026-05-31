<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->enum('type', ['text', 'textarea', 'date', 'boolean', 'select']);
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_custom_field_definitions');
    }
};
