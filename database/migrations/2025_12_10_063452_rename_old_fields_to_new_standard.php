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
        // Rename homes table columns
        if (Schema::hasTable('homes')) {
            Schema::table('homes', function (Blueprint $table) {
                // Add render_mode if it doesn't exist
                if (!Schema::hasColumn('homes', 'render_mode')) {
                    $table->string('render_mode', 50)->default('full_page_grapejs')->after('id');
                }
                if (Schema::hasColumn('homes', 'html') && !Schema::hasColumn('homes', 'body')) {
                    $table->renameColumn('html', 'body');
                }
                if (Schema::hasColumn('homes', 'html_css') && !Schema::hasColumn('homes', 'body_css')) {
                    $table->renameColumn('html_css', 'body_css');
                }
            });
        }

        // Rename pages table columns
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                // Add render_mode if it doesn't exist
                if (!Schema::hasColumn('pages', 'render_mode')) {
                    $table->string('render_mode', 50)->default('full_page_grapejs')->after('id');
                }
                if (Schema::hasColumn('pages', 'titles') && !Schema::hasColumn('pages', 'title')) {
                    $table->renameColumn('titles', 'title');
                }
                if (Schema::hasColumn('pages', 'contents') && !Schema::hasColumn('pages', 'body')) {
                    $table->renameColumn('contents', 'body');
                }
                if (Schema::hasColumn('pages', 'featured_images') && !Schema::hasColumn('pages', 'featured_image')) {
                    $table->renameColumn('featured_images', 'featured_image');
                }
                // Add body_css if it doesn't exist
                if (!Schema::hasColumn('pages', 'body_css')) {
                    $table->text('body_css')->nullable()->after('body');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert homes table columns
        if (Schema::hasTable('homes')) {
            Schema::table('homes', function (Blueprint $table) {
                if (Schema::hasColumn('homes', 'body') && !Schema::hasColumn('homes', 'html')) {
                    $table->renameColumn('body', 'html');
                }
                if (Schema::hasColumn('homes', 'body_css') && !Schema::hasColumn('homes', 'html_css')) {
                    $table->renameColumn('body_css', 'html_css');
                }
                if (Schema::hasColumn('homes', 'render_mode')) {
                    $table->dropColumn('render_mode');
                }
            });
        }

        // Revert pages table columns
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                if (Schema::hasColumn('pages', 'title') && !Schema::hasColumn('pages', 'titles')) {
                    $table->renameColumn('title', 'titles');
                }
                if (Schema::hasColumn('pages', 'body') && !Schema::hasColumn('pages', 'contents')) {
                    $table->renameColumn('body', 'contents');
                }
                if (Schema::hasColumn('pages', 'featured_image') && !Schema::hasColumn('pages', 'featured_images')) {
                    $table->renameColumn('featured_image', 'featured_images');
                }
                if (Schema::hasColumn('pages', 'body_css')) {
                    $table->dropColumn('body_css');
                }
                if (Schema::hasColumn('pages', 'render_mode')) {
                    $table->dropColumn('render_mode');
                }
            });
        }
    }
};
