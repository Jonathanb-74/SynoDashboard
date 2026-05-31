<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('global_attribute_id')->constrained('global_attributes')->cascadeOnDelete();
            $table->foreignId('decoder_model_id')->constrained('json_decoder_models')->cascadeOnDelete();
            $table->string('element_internal_key', 36);
            $table->timestamps();
            $table->unique(['global_attribute_id', 'decoder_model_id'], 'ga_mapping_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_attribute_mappings');
    }
};
