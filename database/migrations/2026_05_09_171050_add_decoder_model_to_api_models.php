<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_models', function (Blueprint $table) {
            $table->foreignId('decoder_model_id')
                  ->nullable()
                  ->after('description')
                  ->constrained('json_decoder_models')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('api_models', function (Blueprint $table) {
            $table->dropForeign(['decoder_model_id']);
            $table->dropColumn('decoder_model_id');
        });
    }
};
