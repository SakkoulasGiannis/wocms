<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('default_lat', 10, 8)->default(37.9838);
            $table->decimal('default_lng', 11, 8)->default(23.7275);
            $table->integer('default_zoom')->default(13);
            $table->integer('min_zoom')->default(3);
            $table->integer('max_zoom')->default(18);
            $table->json('markers')->nullable();
            $table->json('areas')->nullable();
            $table->boolean('show_controls')->default(true);
            $table->boolean('show_search')->default(true);
            $table->boolean('show_legend')->default(false);
            $table->text('legend_html')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('featured')->default(false);
            $table->integer('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('active');
            $table->index('featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maps');
    }
};
