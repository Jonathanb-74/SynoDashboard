<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nas_id')
                  ->constrained('nas_devices')
                  ->cascadeOnDelete();
            $table->string('agent_version', 100)->nullable();
            $table->timestamp('collected_at');
            $table->longText('raw_json');
            $table->longText('decoded_cache')->nullable();
            $table->timestamps();

            $table->index(['nas_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_snapshots');
    }
};
