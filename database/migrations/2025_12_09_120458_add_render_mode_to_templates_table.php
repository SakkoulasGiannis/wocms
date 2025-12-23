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
            $table->enum('render_mode', ['full_page_grapejs', 'sections', 'simple_content'])
                ->default('full_page_grapejs')
                ->after('file_path')
                ->comment('How the template content is rendered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('render_mode');
        });
    }
};
