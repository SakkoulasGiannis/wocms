<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sliders', 'settings')) {
            Schema::table('sliders', function (Blueprint $table) {
                $table->json('settings')->nullable()->after('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sliders', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
