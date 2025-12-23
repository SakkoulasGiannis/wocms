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
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page_type')->default('home'); // home, about, contact, custom_page_slug
            $table->string('section_type'); // hero_slider, about_us, features_grid, etc.
            $table->string('name')->nullable(); // Friendly name for admin
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true);
            $table->json('content'); // Section-specific content (validated per type)
            $table->json('settings')->nullable(); // Section-specific settings (layout, colors, etc)
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['page_type', 'order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
