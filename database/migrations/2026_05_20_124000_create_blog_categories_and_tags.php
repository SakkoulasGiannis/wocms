<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds blog categories and tags as taxonomies for the existing `blogs` table.
 *
 * Tables created (idempotent — guarded by hasTable):
 *   - blog_categories       hierarchical (parent_id), slug-unique
 *   - blog_tags             flat, slug-unique
 *   - blog_blog_category    pivot blogs <-> blog_categories
 *   - blog_blog_tag         pivot blogs <-> blog_tags
 *
 * Strictly additive — existing blogs table and posts are untouched, so this
 * cannot break the live kecms blog (12 posts, model Blog) when deployed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $t): void {
                $t->id();
                $t->foreignId('parent_id')->nullable()
                    ->constrained('blog_categories')->nullOnDelete();
                $t->string('name');
                $t->string('slug')->unique();
                $t->text('description')->nullable();
                $t->unsignedInteger('order')->default(0)->index();
                $t->boolean('is_active')->default(true)->index();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('blog_tags')) {
            Schema::create('blog_tags', function (Blueprint $t): void {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('blog_blog_category')) {
            Schema::create('blog_blog_category', function (Blueprint $t): void {
                $t->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
                $t->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
                $t->primary(['blog_id', 'blog_category_id']);
                $t->index('blog_category_id');
            });
        }

        if (! Schema::hasTable('blog_blog_tag')) {
            Schema::create('blog_blog_tag', function (Blueprint $t): void {
                $t->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
                $t->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
                $t->primary(['blog_id', 'blog_tag_id']);
                $t->index('blog_tag_id');
            });
        }
    }

    public function down(): void
    {
        // Drop pivots first (FK dependents)
        Schema::dropIfExists('blog_blog_tag');
        Schema::dropIfExists('blog_blog_category');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
    }
};
