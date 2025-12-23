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
            // Drop index first (if exists) - important for SQLite
            $table->dropIndex(['page_type', 'order']);

            // Drop old columns
            $table->dropColumn(['page_type']);

            // Add polymorphic relationship (sectionable_type, sectionable_id)
            $table->string('sectionable_type')->nullable()->after('id');
            $table->unsignedBigInteger('sectionable_id')->nullable()->after('sectionable_type');
            $table->index(['sectionable_type', 'sectionable_id']);

            // Add CSS field for section-specific styles
            $table->longText('css')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropIndex(['sectionable_type', 'sectionable_id']);
            $table->dropColumn(['sectionable_type', 'sectionable_id', 'css']);
            $table->string('page_type')->default('home');
        });
    }
};
