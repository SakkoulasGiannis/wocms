<?php

use App\Models\Blog;
use App\Models\BlogTag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Convert the legacy comma-separated `blogs.tags` column into proper
 * BlogTag records + pivot rows, then rename the column to `tags_legacy`
 * so $blog->tags can become the new many-to-many relation instead of
 * shadowing it as an attribute.
 *
 * Idempotent guards on both the data migration and the rename.
 * Reversible — down() renames back and clears the new tag pivots that
 * were created here (best-effort).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blogs')) {
            return;
        }

        // 1. Data migration: parse legacy CSV tags into BlogTag + pivot
        //    Only run while the original column still exists.
        if (Schema::hasColumn('blogs', 'tags')) {
            DB::transaction(function (): void {
                $rows = DB::table('blogs')
                    ->whereNotNull('tags')
                    ->where('tags', '!=', '')
                    ->get(['id', 'tags']);

                foreach ($rows as $row) {
                    $names = collect(explode(',', (string) $row->tags))
                        ->map(fn ($n) => trim((string) $n))
                        ->filter()
                        ->unique()
                        ->values();

                    $pivotIds = [];
                    foreach ($names as $name) {
                        $slug = Str::slug($name);
                        if ($slug === '') {
                            continue;
                        }
                        $tag = BlogTag::firstOrCreate(['slug' => $slug], ['name' => $name]);
                        $pivotIds[] = $tag->id;
                    }
                    if ($pivotIds) {
                        // Use syncWithoutDetaching to be safe if migration runs twice
                        Blog::find($row->id)?->tags()->syncWithoutDetaching($pivotIds);
                    }
                }
            });
        }

        // 2. Rename `tags` → `tags_legacy` so it stops shadowing the relation.
        if (Schema::hasColumn('blogs', 'tags') && ! Schema::hasColumn('blogs', 'tags_legacy')) {
            Schema::table('blogs', function (Blueprint $t): void {
                $t->renameColumn('tags', 'tags_legacy');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blogs') && Schema::hasColumn('blogs', 'tags_legacy') && ! Schema::hasColumn('blogs', 'tags')) {
            Schema::table('blogs', function (Blueprint $t): void {
                $t->renameColumn('tags_legacy', 'tags');
            });
        }
        // Best-effort: clear all pivot rows so a re-run of up() can re-populate cleanly.
        if (Schema::hasTable('blog_blog_tag')) {
            DB::table('blog_blog_tag')->delete();
        }
    }
};
