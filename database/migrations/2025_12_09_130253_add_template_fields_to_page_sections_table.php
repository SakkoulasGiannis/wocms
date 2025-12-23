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
        Schema::table('page_sections', function (Blueprint $table) {
            $table->foreignId('section_template_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->enum('edit_mode', ['simple', 'advanced'])->default('simple')->after('section_type');
            $table->text('rendered_html')->nullable()->after('content'); // Cached rendered HTML

            $table->index('section_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropForeign(['section_template_id']);
            $table->dropColumn(['section_template_id', 'edit_mode', 'rendered_html']);
        });
    }
};
