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
        Schema::create('section_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_template_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Field identifier (e.g., "heading", "image_url")
            $table->string('label'); // Display label (e.g., "Heading", "Background Image")
            $table->string('type'); // text, textarea, wysiwyg, image, gallery, url, email, select, checkbox, repeater, color
            $table->text('description')->nullable();
            $table->string('placeholder')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->json('options')->nullable(); // For select, checkbox, etc.
            $table->json('validation_rules')->nullable();
            $table->json('settings')->nullable(); // Additional field-specific settings
            $table->timestamps();

            $table->index(['section_template_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_template_fields');
    }
};
