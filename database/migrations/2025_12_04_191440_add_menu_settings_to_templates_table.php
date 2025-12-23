<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('show_in_menu')->default(false)->after('is_active');
            $table->string('menu_label')->nullable()->after('show_in_menu');
            $table->string('menu_icon')->nullable()->after('menu_label');
            $table->integer('menu_order')->default(0)->after('menu_icon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['show_in_menu', 'menu_label', 'menu_icon', 'menu_order']);
        });
    }
};
