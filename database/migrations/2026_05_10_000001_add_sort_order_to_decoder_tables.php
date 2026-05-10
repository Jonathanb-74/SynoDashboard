<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('json_decoder_fields', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('json_path');
        });

        Schema::table('json_decoder_loops', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('json_path');
        });

        Schema::table('json_decoder_loop_fields', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('json_path');
        });
    }

    public function down(): void
    {
        Schema::table('json_decoder_fields',     fn (Blueprint $t) => $t->dropColumn('sort_order'));
        Schema::table('json_decoder_loops',      fn (Blueprint $t) => $t->dropColumn('sort_order'));
        Schema::table('json_decoder_loop_fields',fn (Blueprint $t) => $t->dropColumn('sort_order'));
    }
};
