<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Library of reusable "card" designs used by SectionEmbed blocks.
 *
 * A card template is essentially an EditorJS / HTML snippet authored once and
 * then rendered N times per loop iteration, with token substitution
 * ({title}, {image}, {entry_url}, …) producing per-row output.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            // Rendered HTML string with token placeholders ({title}, {image}, …).
            // Stored long because Tailwind hero markup gets verbose.
            $table->longText('html');
            // Optional source template slug to scope this card to (e.g. "property")
            // so the picker can filter by entity type. NULL = available everywhere.
            $table->string('source_template_slug')->nullable()->index();
            $table->string('category')->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_templates');
    }
};
