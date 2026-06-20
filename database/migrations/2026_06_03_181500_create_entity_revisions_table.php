<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_revisions', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner — any Eloquent model can be snapshotted.
            // Naming "entity_type"/"entity_id" rather than morphs() so the
            // controller layer stays explicit about who owns the snapshot.
            $table->string('entity_type', 191);
            $table->unsignedBigInteger('entity_id');

            // Full field values payload — same shape EntityFieldsCompiler
            // consumes (assoc array keyed by template field name).
            $table->longText('fields_json');

            // What triggered the snapshot. Same vocabulary as page_revisions:
            //   pre-ai-create / post-ai-create / pre-ai-edit / post-ai-edit
            $table->string('source', 32)->index();

            // The user prompt that drove the AI op (verbatim).
            $table->text('prompt')->nullable();

            // Which admin user triggered it. Nullable for CLI/cron paths.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Hot path: list newest-first revisions for a specific entity.
            $table->index(['entity_type', 'entity_id', 'created_at'], 'entity_revisions_owner_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_revisions');
    }
};
