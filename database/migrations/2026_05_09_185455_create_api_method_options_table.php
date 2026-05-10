<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_method_options', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('api_method_options')->insert([
            ['name' => 'get',        'sort_order' => 10,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'get_info',   'sort_order' => 20,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'info',       'sort_order' => 30,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'list',       'sort_order' => 40,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'load_info',  'sort_order' => 50,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'check',      'sort_order' => 60,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create',     'sort_order' => 70,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete',     'sort_order' => 80,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit',       'sort_order' => 90,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'enable',     'sort_order' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'disable',    'sort_order' => 110, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'query',      'sort_order' => 120, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'download',   'sort_order' => 130, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'save',       'sort_order' => 140, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'set',        'sort_order' => 150, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'start',      'sort_order' => 160, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'status',     'sort_order' => 170, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'stop',       'sort_order' => 180, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'test',       'sort_order' => 190, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'upload',     'sort_order' => 200, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_method_options');
    }
};
