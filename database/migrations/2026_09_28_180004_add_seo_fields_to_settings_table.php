<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('site_name')->nullable()->after('exchange_rate');
            $table->text('default_meta_description')->nullable()->after('site_name');
            $table->string('default_og_image')->nullable()->after('default_meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['site_name', 'default_meta_description', 'default_og_image']);
        });
    }
};
