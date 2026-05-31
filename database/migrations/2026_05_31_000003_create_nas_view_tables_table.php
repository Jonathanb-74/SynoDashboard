<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_view_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_nas_page_default')->default(false);
            $table->boolean('is_dashboard_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_view_tables');
    }
};
