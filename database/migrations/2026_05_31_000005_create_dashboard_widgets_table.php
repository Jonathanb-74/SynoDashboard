<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['builtin', 'count']);
            $table->string('builtin_key', 32)->nullable();
            $table->string('label');
            $table->boolean('active')->default(true);
            $table->string('color', 20)->default('primary');
            $table->unsignedSmallInteger('position')->default(0);
            // For count widgets
            $table->enum('source', ['device', 'custom_field'])->nullable();
            $table->string('field_key', 64)->nullable();
            $table->string('field_value', 255)->nullable();
            $table->timestamps();
        });

        // Seed the 4 built-in widgets
        $now = now()->toDateTimeString();
        DB::table('dashboard_widgets')->insert([
            ['type' => 'builtin', 'builtin_key' => 'total',    'label' => 'Total NAS',    'active' => 1, 'color' => 'primary', 'position' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'builtin', 'builtin_key' => 'approved', 'label' => 'Approuvés',    'active' => 1, 'color' => 'success', 'position' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'builtin', 'builtin_key' => 'pending',  'label' => 'En attente',   'active' => 1, 'color' => 'warning', 'position' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'builtin', 'builtin_key' => 'rejected', 'label' => 'Rejetés',      'active' => 1, 'color' => 'danger',  'position' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
