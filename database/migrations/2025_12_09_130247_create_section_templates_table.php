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
        Schema::create('section_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Hero Simple"
            $table->string('slug')->unique(); // e.g., "hero-simple"
            $table->string('category')->default('content'); // hero, content, cta, forms, etc.
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable(); // Preview image path
            $table->text('html_template'); // Blade template with {{placeholders}}
            $table->string('blade_file')->nullable(); // Physical file path
            $table->boolean('is_system')->default(false); // Can't be deleted
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->json('default_settings')->nullable(); // Default settings JSON
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_templates');
    }
};
