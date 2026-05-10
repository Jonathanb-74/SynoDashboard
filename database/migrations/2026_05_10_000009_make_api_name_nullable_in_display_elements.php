<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('display_elements', function (Blueprint $table) {
            $table->string('api_name', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('display_elements', function (Blueprint $table) {
            $table->string('api_name', 191)->nullable(false)->change();
        });
    }
};
