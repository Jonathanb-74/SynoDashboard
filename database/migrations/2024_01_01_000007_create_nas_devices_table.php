<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('model', 191)->nullable();
            $table->string('serial', 191)->unique();
            $table->string('dsm_version', 50)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('api_model_id')
                  ->nullable()
                  ->constrained('api_models')
                  ->nullOnDelete();
            $table->foreignId('decoder_model_id')
                  ->nullable()
                  ->constrained('json_decoder_models')
                  ->nullOnDelete();
            $table->unsignedSmallInteger('collection_frequency')->default(60);
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('serial');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_devices');
    }
};
