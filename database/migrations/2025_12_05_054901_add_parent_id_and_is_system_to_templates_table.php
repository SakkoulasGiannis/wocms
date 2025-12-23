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
            // Parent template for hierarchy
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('templates')->onDelete('cascade');

            // System template flag (cannot be deleted)
            $table->boolean('is_system')->default(false)->after('is_active');

            // Tree structure helpers
            $table->integer('tree_level')->default(0)->after('is_system');
            $table->string('tree_path', 500)->nullable()->after('tree_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_system', 'tree_level', 'tree_path']);
        });
    }
};
