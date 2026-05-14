<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nas_devices', function (Blueprint $table) {
            $table->string('hmac_secret', 64)->nullable()->after('approved_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('nas_devices', function (Blueprint $table) {
            $table->dropColumn('hmac_secret');
        });
    }
};
