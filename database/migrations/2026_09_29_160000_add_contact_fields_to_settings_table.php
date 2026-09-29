<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('contact_title')->nullable()->after('default_og_image');
            $table->string('contact_subtitle')->nullable()->after('contact_title');
            $table->string('contact_phone')->nullable()->after('contact_subtitle');
            $table->string('contact_phone_hours')->nullable()->after('contact_phone');
            $table->string('contact_address')->nullable()->after('contact_phone_hours');
            $table->string('contact_map_url')->nullable()->after('contact_address');
            $table->string('contact_email')->nullable()->after('contact_map_url');
            $table->string('contact_email_hint')->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_title',
                'contact_subtitle',
                'contact_phone',
                'contact_phone_hours',
                'contact_address',
                'contact_map_url',
                'contact_email',
                'contact_email_hint',
            ]);
        });
    }
};
