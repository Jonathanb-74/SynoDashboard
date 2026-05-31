<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE nas_view_columns MODIFY COLUMN source ENUM('device','custom_field','global_attribute') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE nas_view_columns MODIFY COLUMN source ENUM('device','custom_field') NOT NULL");
    }
};
