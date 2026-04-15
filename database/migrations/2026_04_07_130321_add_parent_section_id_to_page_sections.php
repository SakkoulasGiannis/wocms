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
            $table->unsignedBigInteger('parent_section_id')->nullable()->after('id');
            $table->foreign('parent_section_id')->references('id')->on('page_sections')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropForeign(['parent_section_id']);
            $table->dropColumn('parent_section_id');
        });
    }
};
