<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Methods that are read-only / query-only — safe to probe blindly
    private const SAFE_METHODS = ['check', 'get', 'get_info', 'info', 'list', 'load_info', 'query', 'status', 'test'];

    public function up(): void
    {
        Schema::table('api_method_options', function (Blueprint $table) {
            $table->boolean('debug_enabled')->default(false)->after('sort_order');
        });

        DB::table('api_method_options')
            ->whereIn('name', self::SAFE_METHODS)
            ->update(['debug_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('api_method_options', function (Blueprint $table) {
            $table->dropColumn('debug_enabled');
        });
    }
};
