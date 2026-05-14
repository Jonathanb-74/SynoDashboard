<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nas_id')->nullable()->constrained('nas_devices')->nullOnDelete();
            $table->string('nas_serial', 100)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('http_method', 10)->default('POST');
            $table->string('path', 500);
            $table->unsignedSmallInteger('status_code');
            $table->longText('payload')->nullable();
            $table->text('response')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
