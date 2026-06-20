<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();

            // The Page this revision belongs to. Cascade delete: when the
            // page is permanently deleted, its history goes with it.
            $table->foreignId('page_id')
                ->constrained('pages')
                ->cascadeOnDelete();

            // Full exported spec (page columns + sections tree + EditorJS blocks)
            // — same shape as `php artisan page:export`. We use longText so
            // even huge multi-section pages with lots of blocks fit.
            $table->longText('spec');

            // What caused this revision. Used to filter the history UI.
            //   pre-ai-create  → snapshot of nothing (page didn't exist before)
            //   post-ai-create → first snapshot of a newly AI-created page
            //   pre-ai-edit    → snapshot right BEFORE an AI edit (the rollback point)
            //   post-ai-edit   → snapshot right AFTER a successful AI edit
            $table->string('source', 32)->index();

            // The user's prompt that triggered this AI operation. Plain text,
            // shown in the history UI as "AI ζητήθηκε: …" so the editor knows
            // what they originally asked for.
            $table->text('prompt')->nullable();

            // Which admin user triggered the AI op. Nullable so CLI / cron
            // invocations still record cleanly.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Hot path: list revisions for a page newest-first.
            $table->index(['page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_revisions');
    }
};
